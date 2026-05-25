<?php

namespace App\Filament\Pages;

use App\Actions\SendPendingWalletDepositApprovalEmailsAction;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Filament\Forms\Components\PaymentMethodDetails;
use App\Models\WalletTransaction;
use DB;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class Wallet extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected string $view = 'filament.pages.wallet';

    protected ?string $heading = 'My Wallet';

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return $table
            ->query(WalletTransaction::query()->whereBelongsTo($user))
            ->heading('Wallet Transactions')
            ->description(new HtmlString('Balance: <strong class="text-primary-600 dark:text-primary-400 font-bold" style="font-size: 1.1em;">'.number_format($user->wallet_balance ?? 0, 2).' BDT</strong>'))
            ->headerActions([
                Action::make('deposit')
                    ->label('Add Funds')
                    ->icon('heroicon-o-plus')
                    ->modalWidth(Width::Large)
                    ->schema([
                        Select::make('payment_method_id')
                            ->label('Payment Method')
                            ->options(function () use ($user) {
                                return $user->paymentMethods()->active()->pluck('name', 'payment_methods.id');
                            })
                            ->required()
                            ->searchable(),
                        TextInput::make('amount')
                            ->label('Amount (BDT)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->live(),
                        ViewField::make('deposit_summary')
                            ->view('filament.forms.components.deposit-summary')
                            ->visibleJs('!! $get(\'amount\') && !! $get(\'payment_method_id\')'),
                        PaymentMethodDetails::make('selected_payment_method_details')
                            ->paymentMethods(PaymentMethodDetails::getPaymentMethodsForView($user))
                            ->visibleJs('!! $get(\'payment_method_id\')'),
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

                            app(SendPendingWalletDepositApprovalEmailsAction::class)($transaction);
                        });

                        Notification::make()
                            ->title('Deposit request submitted for approval.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->searchable(),
                TextColumn::make('processing_fee')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('payable_amount')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('balance_after')
                    ->label('Balance')
                    ->money('BDT')
                    ->sortable(),
            ])
            ->recordAction('viewProof')
            ->recordActions([
                Action::make('viewProof')
                    ->label('Proof of Payment')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->slideOver()
                    ->modalWidth(Width::Medium)
                    ->modalHeading('Proof of Payment')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(fn (WalletTransaction $record) => view('filament.pages.partials.wallet-transaction-proof', [
                        'record' => $record,
                    ])),
            ]);
    }

    private function handleScreenshots(array $screenshots): ?array
    {
        if (empty($screenshots)) {
            return null;
        }

        $finalPaths = [];
        foreach ($screenshots as $screenshot) {
            // Handle UploadedFile objects (e.g. TemporaryUploadedFile from Livewire)
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

            // Ensure screenshot is a string for further checks
            if (! is_string($screenshot)) {
                continue;
            }

            // If it's already a permanent path, keep it
            if (! str_starts_with($screenshot, 'livewire-file:')) {
                $finalPaths[] = $screenshot;

                continue;
            }

            // Handle Livewire temporary upload from string (if applicable)
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
}
