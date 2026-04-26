<?php

namespace App\Filament\Pages;

use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Models\PriceRate;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PricingPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected string $view = 'filament.pages.pricing-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PriceRate::query()
                    ->whereHas('adAccount', function ($query) {
                        $query->whereBelongsTo(Filament::auth()->user());
                    })
                    ->orWhereNull('ad_account_id')
                    ->with('adAccount')
                    ->orderByRaw('ad_account_id is null desc')
            )
            ->defaultGroup('adAccount.name')
            ->defaultSort('min_usd', 'asc')
            ->columns([
                TextColumn::make('adAccount.name')
                    ->label('Ad Account')
                    ->placeholder('Global')
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
            ]);
    }
}
