<?php

namespace App\Filament\Admin\Resources\WalletTransactions\Tables;

use App\Enums\WalletTransactionStatus;
use App\Models\WalletTransaction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class WalletTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('balance_after')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('paymentMethod.name')
                    ->searchable(),
                TextColumn::make('adAccount.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
                        ])),
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (WalletTransaction $record): bool => $record->status === WalletTransactionStatus::PENDING)
                        ->action(function (WalletTransaction $record) {
                            DB::transaction(function () use ($record) {
                                $record->user->wallet_balance += $record->amount;
                                $record->user->save();

                                $record->update([
                                    'status' => WalletTransactionStatus::APPROVED,
                                    'admin_id' => Filament::auth()->id(),
                                    'approved_at' => now(),
                                    'balance_after' => $record->user->wallet_balance,
                                ]);
                            });
                        }),
                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (WalletTransaction $record): bool => $record->status === WalletTransactionStatus::PENDING)
                        ->action(function (WalletTransaction $record) {
                            $record->update([
                                'status' => WalletTransactionStatus::REJECTED,
                                'admin_id' => Filament::auth()->id(),
                            ]);
                        }),
                    EditAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
