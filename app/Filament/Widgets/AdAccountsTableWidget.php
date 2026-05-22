<?php

namespace App\Filament\Widgets;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Filament\Actions\DepositFundAction;
use App\Filament\Forms\Components\PaymentMethodDetails;
use App\Filament\Pages\OrderHistory;
use App\Filament\Tables\Columns\AdAccountsTable\AdAccountColumn;
use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Mail\NewWalletDepositPendingApprovalMail;
use App\Models\AdAccount;
use App\Models\Order;
use App\Models\WalletTransaction;
use App\Services\FacebookAdAccountService;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;

class AdAccountsTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -2;

    protected string $view = 'filament.widgets.ad-accounts-table-widget';

    #[Computed]
    public function stats(): array
    {
        $accounts = AdAccount::query()->whereBelongsTo(Filament::auth()->user())->get();
        $accountsCount = $accounts->count();
        $lastSynced = $accounts->max('synced_at');

        $activeAccountIds = Order::query()
            ->whereIn('ad_account_id', $accounts->pluck('id'))
            ->where('created_at', '>=', now()->subMonth())
            ->pluck('ad_account_id')
            ->unique();

        $inactiveCount = $accountsCount - $activeAccountIds->count();

        return [
            [
                'label' => 'Total Ad Accounts',
                'value' => $accountsCount,
                'subtext' => 'Managed by you',
                'icon' => 'heroicon-o-users',
                'icon_color' => 'text-blue-500',
                'icon_bg' => 'bg-blue-50 dark:bg-blue-500/10',
            ],
            [
                'label' => 'Inactive Accounts',
                'value' => $inactiveCount,
                'subtext' => '30 days no orders',
                'icon' => 'heroicon-o-exclamation-triangle',
                'icon_color' => 'text-red-500',
                'icon_bg' => 'bg-red-50 dark:bg-red-500/10',
            ],
            [
                'label' => 'Last Synced',
                'value' => $lastSynced ? $lastSynced->format('h:i A') : 'N/A',
                'subtext' => $lastSynced ? $lastSynced->format('d-M-Y') : 'N/A',
                'icon' => 'heroicon-o-arrow-path',
                'icon_color' => 'text-green-500', // actually default green here is taken over by previous, let's keep it
                'icon_bg' => 'bg-green-50 dark:bg-green-500/10',
            ],
        ];
    }

    public function depositAction(): Action
    {
        $user = Filament::auth()->user();

        return Action::make('deposit')
            ->label('Add Funds')
            ->icon('heroicon-o-plus')
            ->button()
            ->color('success')
            ->modalWidth(Width::Large)
            ->schema([
                Select::make('payment_method_id')
                    ->label('Payment Method')
                    ->options(function () use ($user) {
                        return $user->paymentMethods()->active()->pluck('name', 'payment_methods.id');
                    })
                    ->required()
                    ->searchable(),
                PaymentMethodDetails::make('selected_payment_method_details')
                    ->paymentMethods(PaymentMethodDetails::getPaymentMethodsForView($user))
                    ->visibleJs('!! $get(\'payment_method_id\')'),
                TextInput::make('amount')
                    ->label('Amount (BDT)')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                ViewField::make('screenshots')
                    ->view('filament.forms.components.custom-file-upload')
                    ->required(),
                Textarea::make('note')
                    ->label('Note (optional)')
                    ->maxLength(500)
                    ->visible(fn () => Filament::getCurrentPanel()?->getId() === 'admin'),
            ])
            ->action(function (array $data) use ($user) {
                DB::transaction(function () use ($data, $user) {
                    $transaction = WalletTransaction::create([
                        'user_id' => $user->id,
                        'type' => WalletTransactionType::DEPOSIT,
                        'amount' => $data['amount'],
                        'payment_method_id' => $data['payment_method_id'],
                        'status' => WalletTransactionStatus::PENDING,
                        'note' => $data['note'] ?? null,
                        'screenshots' => $this->handleScreenshots($data['screenshots'] ?? []),
                    ]);

                    // Send approval email
                    $approveUrl = url('/admin/wallet-transactions');
                    $rejectUrl = url('/admin/wallet-transactions');

                    Mail::to(config('mail.admin_address', 'admin@mbizcrm.test'))
                        ->send(new NewWalletDepositPendingApprovalMail($transaction, $approveUrl, $rejectUrl));
                });

                Notification::make()
                    ->title('Deposit request submitted successfully')
                    ->body('Your request is pending administrative approval.')
                    ->success()
                    ->send();
            });
    }

    private function handleScreenshots(array $screenshots): ?array
    {
        if (empty($screenshots)) {
            return null;
        }

        $finalPaths = [];
        foreach ($screenshots as $screenshot) {
            if ($screenshot instanceof UploadedFile) {
                try {
                    $path = $screenshot->store('wallet/screenshots', 'public');
                    if ($path) {
                        $finalPaths[] = $path;
                    }
                } catch (\Throwable $e) {
                    report($e);
                }

                continue;
            }

            if (is_string($screenshot) && str_starts_with($screenshot, 'livewire-tmp/')) {
                $tempPath = $screenshot;
                $newPath = 'wallet/screenshots/'.basename($tempPath);

                if (Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->move($tempPath, $newPath);
                    $finalPaths[] = $newPath;
                }

                continue;
            }

            if (is_string($screenshot)) {
                $finalPaths[] = $screenshot;
            }
        }

        return ! empty($finalPaths) ? $finalPaths : null;
    }

    public function syncSingle(int $id): void
    {
        $account = AdAccount::query()
            ->whereBelongsTo(Filament::auth()->user())
            ->findOrFail($id);

        try {
            app(FacebookAdAccountService::class)->syncSingleAdAccount($account);

            Notification::make()
                ->title('Ad account synced successfully.')
                ->success()
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->title('Ad account sync failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function syncAll(): void
    {
        $accounts = AdAccount::query()
            ->whereBelongsTo(Filament::auth()->user())
            ->get()
            ->filter(fn (AdAccount $account) => $account->status->isActive());

        if ($accounts->isEmpty()) {
            Notification::make()
                ->title('No active ad accounts found to sync.')
                ->warning()
                ->send();

            return;
        }

        try {
            $service = app(FacebookAdAccountService::class);
            foreach ($accounts as $account) {
                $service->syncSingleAdAccount($account);
            }

            Notification::make()
                ->title($accounts->count().' ad accounts synced successfully.')
                ->success()
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->title('Ad accounts sync failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(null)
            ->extraAttributes(['class' => 'ad-accounts-table'])
            ->query(AdAccount::query()->whereBelongsTo(Filament::auth()->user()))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('#')
                    ->rowIndex()
                    ->alignCenter(),
                AdAccountColumn::make('name')
                    ->label('Ad Account')
                    ->searchable(),
                CurrencyColumn::make('spend_cap')
                    ->label('Limit'),
                CurrencyColumn::make('amount_spent')
                    ->label('Spent'),
                DateTimeColumn::make('synced_at'),
            ])
            ->recordAction('orders')
            ->recordActions([
                Action::make('orders')
                    ->label('Orders')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->extraModalOverlayAttributes(['class' => 'orders-modal-overlay'])
                    ->extraModalWindowAttributes(['class' => 'orders-modal-window'])
                    ->modalContent(fn (AdAccount $record) => view('filament.actions.ad-account-view-orders', [
                        'record' => $record,
                        'table' => 'ad-accounts',
                        'orderHistoryClass' => OrderHistory::class,
                    ]))
                    ->modalHeading(fn (AdAccount $record) => $record->name.'- Order History')
                    ->modalCloseButton()
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->extraAttributes(['class' => 'hidden']),
                DepositFundAction::make()->button(),
            ], RecordActionsPosition::BeforeCells)
            ->content(fn () => view('filament.tables.custom-ad-accounts-table'));
    }
}
