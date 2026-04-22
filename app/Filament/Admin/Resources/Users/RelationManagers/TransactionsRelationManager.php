<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('approvedByAdmin'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('amount')
                    ->money('BDT'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('approvedByAdmin.email')
                    ->label('Approved By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('current_balance')
                    ->label(function (): string {
                        $currentBalance = (float) ($this->getOwnerRecord()->wallet?->balance ?? 0);

                        return 'Current Balance: BDT '.number_format($currentBalance, 2);
                    })
                    ->color('gray')
                    ->disabled(),
            ])
            ->recordActions([]);
    }
}
