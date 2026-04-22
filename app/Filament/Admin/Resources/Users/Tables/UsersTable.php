<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('wallet'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('wallet.balance')
                    ->label('Wallet Balance')
                    ->money('BDT')
                    ->default('0.00')
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('deposit')
                    ->label('Deposit')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->schema([
                        TextInput::make('amount')
                            ->label('Amount (BDT)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->step(0.01),
                        Textarea::make('note')
                            ->maxLength(500),
                    ])
                    ->action(function ($record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $wallet = Wallet::query()->firstOrCreate(
                                ['user_id' => $record->id],
                                ['balance' => 0]
                            );

                            $transaction = Transaction::query()->create([
                                'wallet_id' => $wallet->id,
                                'user_id' => $record->id,
                                'approved_by_admin_id' => Filament::auth()->id(),
                                'type' => Transaction::TYPE_DEPOSIT,
                                'source' => Transaction::SOURCE_ADMIN,
                                'status' => Transaction::STATUS_APPROVED,
                                'amount' => $data['amount'],
                                'note' => $data['note'] ?? null,
                                'approved_at' => now(),
                            ]);

                            $wallet->credit((float) $transaction->amount);
                        });

                        Notification::make()
                            ->success()
                            ->title('Wallet deposited successfully.')
                            ->send();
                    }),
                Action::make('withdraw')
                    ->label('Withdraw')
                    ->color('danger')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->modalHeading('Withdraw from Wallet')
                    ->modalDescription(function ($record): string {
                        $currentBalance = (float) ($record->wallet?->balance ?? 0);

                        return 'Current wallet balance: BDT '.number_format($currentBalance, 2);
                    })
                    ->schema([
                        TextInput::make('amount')
                            ->label('Amount (BDT)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->step(0.01),
                        Textarea::make('note')
                            ->maxLength(500),
                    ])
                    ->action(function ($record, array $data): void {
                        try {
                            DB::transaction(function () use ($record, $data): void {
                                $wallet = Wallet::query()->firstOrCreate(
                                    ['user_id' => $record->id],
                                    ['balance' => 0]
                                );

                                $transaction = Transaction::query()->create([
                                    'wallet_id' => $wallet->id,
                                    'user_id' => $record->id,
                                    'approved_by_admin_id' => Filament::auth()->id(),
                                    'type' => Transaction::TYPE_WITHDRAWAL,
                                    'source' => Transaction::SOURCE_ADMIN,
                                    'status' => Transaction::STATUS_APPROVED,
                                    'amount' => $data['amount'],
                                    'note' => $data['note'] ?? null,
                                    'approved_at' => now(),
                                ]);

                                $wallet->debit((float) $transaction->amount);
                            });

                            Notification::make()
                                ->success()
                                ->title('Wallet withdrawn successfully.')
                                ->send();
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->danger()
                                ->title($exception->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
