<?php

namespace App\Filament\Pages;

use App\Actions\ApproveWalletTransactionAction;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\AnalyzePaymentScreenshotJob;
use App\Models\WalletTransaction;
use App\Services\GeminiVisionService;
use App\Services\PaymentVerificationEngine;
use DB;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Number;

class Wallet extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected string $view = 'filament.pages.wallet';

    protected ?string $heading = 'Wallet';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Wallet Transactions';

    public static function isAdminPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public static function configureTable(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->extraAttributes(['class' => 'wallet-transactions-table'])
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->visible(fn () => self::isAdminPanel()),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->description(fn (WalletTransaction $record) => $record->created_at->format('h:i A')),
                TextColumn::make('type')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('paymentMethod.name')
                    ->label('Payment Method')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable()
                    ->description(fn (WalletTransaction $record) => Number::currency($record->processing_fee, 'BDT')),
                TextColumn::make('payable_amount')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('balance_after')
                    ->label('Balance')
                    ->money('BDT')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('adAccount.name')
                    ->searchable()
                    ->visible(fn () => self::isAdminPanel())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable()
                    ->visible(fn () => self::isAdminPanel())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('User')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => self::isAdminPanel()),
            ])
            ->recordAction('viewProof')
            ->recordActions([
                ActionGroup::make([
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
                        ]))
                        ->modalFooterActions([
                            Action::make('reanalyzeAi')
                                ->label('Re-run AI Check')
                                ->icon('heroicon-o-arrow-path')
                                ->color('gray')
                                ->visible(fn (WalletTransaction $record): bool => self::isAdminPanel() && $record->type === WalletTransactionType::DEPOSIT && ! empty($record->screenshots))
                                ->action(function (WalletTransaction $record) {
                                    app(AnalyzePaymentScreenshotJob::class, [
                                        'verifiableType' => WalletTransaction::class,
                                        'verifiableId' => $record->id,
                                    ])->handle(
                                        app(GeminiVisionService::class),
                                        app(PaymentVerificationEngine::class),
                                    );

                                    Notification::make()
                                        ->title('AI Analysis Updated')
                                        ->success()
                                        ->send();
                                }),
                            Action::make('editAmount')
                                ->label('Edit Amount')
                                ->icon('heroicon-o-pencil')
                                ->color('warning')
                                ->slideOver()
                                ->modalWidth(Width::Medium)
                                ->schema([
                                    TextEntry::make('transaction_details')
                                        ->label('Transaction Details')
                                        ->state(fn (WalletTransaction $record) => view('filament.pages.partials.wallet-transaction-proof', [
                                            'record' => $record,
                                        ])),
                                    TextInput::make('amount')
                                        ->required()
                                        ->numeric()
                                        ->default(fn (WalletTransaction $record) => $record->amount),
                                ])
                                ->action(function (WalletTransaction $record, array $data) {
                                    $record->update(['amount' => $data['amount']]);
                                })
                                ->cancelParentActions()
                                ->visible(fn (WalletTransaction $record): bool => self::isAdminPanel() && $record->type === WalletTransactionType::DEPOSIT && $record->status === WalletTransactionStatus::PENDING),
                            Action::make('approve')
                                ->label('Approve')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->requiresConfirmation()
                                ->cancelParentActions()
                                ->visible(fn (WalletTransaction $record): bool => self::isAdminPanel() && $record->type === WalletTransactionType::DEPOSIT && $record->status === WalletTransactionStatus::PENDING)
                                ->action(function (WalletTransaction $record) {
                                    app(ApproveWalletTransactionAction::class)($record, Filament::auth()->user());
                                }),
                            Action::make('reject')
                                ->label('Reject')
                                ->icon('heroicon-o-x-circle')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->cancelParentActions()
                                ->visible(fn (WalletTransaction $record): bool => self::isAdminPanel() && $record->type === WalletTransactionType::DEPOSIT && $record->status !== WalletTransactionStatus::REJECTED)
                                ->action(function (WalletTransaction $record) {
                                    DB::transaction(function () use ($record) {
                                        if ($record->status === WalletTransactionStatus::APPROVED) {
                                            $record->user->wallet_balance -= $record->amount;
                                            $record->user->save();
                                        }

                                        $record->update([
                                            'status' => WalletTransactionStatus::REJECTED,
                                            'admin_id' => Filament::auth()->id(),
                                        ]);
                                    });
                                }),
                        ]),
                ])
                    ->extraAttributes(['class' => 'hidden']),
            ])
            ->bulkActions([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();

        return self::configureTable($table)
            ->query(WalletTransaction::query()->when(! self::isAdminPanel(), fn ($q) => $q->whereBelongsTo($user))->when(self::isAdminPanel(), fn ($q) => $q->with('user')))
            ->heading(null)
            ->extraAttributes(['data-balance' => number_format($user->wallet_balance ?? 0, 2)]);
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
