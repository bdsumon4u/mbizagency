<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use RuntimeException;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        $isAdminPanel = Filament::getCurrentPanel()?->getId() === 'admin';

        return $table
            ->modifyQueryUsing(function ($query) use ($isAdminPanel) {
                if ($isAdminPanel) {
                    return $query->with(['user', 'approvedByAdmin']);
                }

                return $query
                    ->where('user_id', Filament::auth()->id())
                    ->with(['user', 'approvedByAdmin']);
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->visible($isAdminPanel),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('amount')
                    ->money('BDT'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('note')
                    ->label('Transaction Log')
                    ->limit(80)
                    ->toggleable(),
                TextColumn::make('approvedByAdmin.email')
                    ->label('Approved By')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible($isAdminPanel),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->visible($isAdminPanel),
                Action::make('approve')
                    ->visible(fn (Transaction $record): bool => $isAdminPanel && $record->status === Transaction::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->action(function (Transaction $record): void {
                        try {
                            $admin = Filament::auth()->user();
                            $record->approve($admin);

                            Notification::make()
                                ->success()
                                ->title('Transaction approved successfully.')
                                ->send();
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->danger()
                                ->title($exception->getMessage())
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->visible(fn (Transaction $record): bool => $isAdminPanel && $record->status === Transaction::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->action(function (Transaction $record): void {
                        try {
                            $admin = Filament::auth()->user();
                            $record->reject($admin);

                            Notification::make()
                                ->success()
                                ->title('Transaction rejected successfully.')
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
                    DeleteBulkAction::make()
                        ->visible($isAdminPanel),
                ]),
            ]);
    }
}
