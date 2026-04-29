<?php

namespace App\Filament\Admin\Resources\PriceRates\Tables;

use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PriceRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('adAccount')->orderByRaw('ad_account_id is null desc'))
            ->defaultGroup('adAccount.name')
            ->defaultSort('min_usd', 'asc')
            ->columns([
                TextColumn::make('adAccount.name')
                    ->label('Ad Account')
                    ->placeholder('Regular Rate')
                    ->searchable()
                    ->sortable(),
                CurrencyColumn::make('min_usd')
                    ->label('Min USD')
                    ->searchable()
                    ->sortable(),
                CurrencyColumn::make('dollar_rate', 'BDT')
                    ->label('Dollar Rate')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime(),
                DateTimeColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('ad_account_id')
                    ->label('Ad Account')
                    ->relationship('adAccount', 'name', function ($query) {
                        $query->whereHas('priceRates');
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
