<?php

namespace App\Filament\Admin\Resources\PriceRates;

use App\Filament\Admin\Resources\PriceRates\Pages\CreatePriceRate;
use App\Filament\Admin\Resources\PriceRates\Pages\EditPriceRate;
use App\Filament\Admin\Resources\PriceRates\Pages\ListPriceRates;
use App\Filament\Admin\Resources\PriceRates\Schemas\PriceRateForm;
use App\Filament\Admin\Resources\PriceRates\Tables\PriceRatesTable;
use App\Models\PriceRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PriceRateResource extends Resource
{
    protected static ?string $model = PriceRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $recordTitleAttribute = 'min_usd';

    public static function getNavigationGroup(): ?string
    {
        return 'Pricing';
    }

    public static function form(Schema $schema): Schema
    {
        return PriceRateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PriceRatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPriceRates::route('/'),
            // 'create' => CreatePriceRate::route('/create'),
            'edit' => EditPriceRate::route('/{record}/edit'),
        ];
    }
}
