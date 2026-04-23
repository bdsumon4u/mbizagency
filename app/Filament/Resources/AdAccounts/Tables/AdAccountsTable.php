<?php

namespace App\Filament\Resources\AdAccounts\Tables;

use App\Enums\AdAccountStatus;
use App\Filament\Actions\DepositFundAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->where('user_id', Filament::auth()->id())
                ->with('businessManager'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('businessManager.name')
                    ->label('Business Manager')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Ad Account')
                    ->searchable(),
                TextColumn::make('act_id')
                    ->label('Ad Account ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (int|string $state): string => AdAccountStatus::tryFrom((int) $state)?->getLabel() ?? (string) $state),
                TextColumn::make('currency'),
                TextColumn::make('balance')
                    ->numeric(),
                TextColumn::make('spend_cap')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('synced_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                DepositFundAction::make(),
            ])
            ->toolbarActions([]);
    }
}
