<?php

namespace App\Filament\Pages;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Filament\Actions\DepositFundAction;
use App\Filament\Forms\Components\PaymentMethodDetails;
use App\Filament\Tables\Columns\AdAccountsTable\AdAccountColumn;
use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Mail\NewWalletDepositPendingApprovalMail;
use App\Models\AdAccount;
use App\Models\WalletTransaction;
use App\Services\FacebookAdAccountService;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AdAccounts extends Page implements HasTable
{
    use InteractsWithTable;

    protected ?string $heading = '';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected string $view = 'filament.pages.ad-accounts';

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
                    ->title('Deposit request submitted for approval.')
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

            if (! is_string($screenshot)) {
                continue;
            }

            if (! str_starts_with($screenshot, 'livewire-file:')) {
                $finalPaths[] = $screenshot;

                continue;
            }

            try {
                $tempPath = str_replace('livewire-file:', '', $screenshot);
                $newPath = 'wallet/screenshots/'.basename($tempPath);

                if (Storage::disk('local')->exists('livewire-tmp/'.$tempPath)) {
                    Storage::disk('public')->put(
                        $newPath,
                        Storage::disk('local')->get('livewire-tmp/'.$tempPath)
                    );
                    $finalPaths[] = $newPath;
                }
            } catch (\Throwable $e) {
                report($e);
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
            ->query(AdAccount::query()
                ->whereBelongsTo(Filament::auth()->user())
                ->when(request()->query('highlight'), fn ($query, $id) => $query->orderByRaw('id = ? desc', [$id]))
            )
            ->defaultSort('id', 'desc')
            ->content(fn () => view('filament.tables.custom-ad-accounts-table'))
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
                    ->modalHeading(fn (AdAccount $record) => $record->name.' - Orders')
                    ->modalCloseButton()
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
                DepositFundAction::make()->button(),
            ], RecordActionsPosition::BeforeCells);
    }
}
