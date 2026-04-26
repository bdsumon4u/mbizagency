<?php

namespace App\Filament\Admin\Resources\PriceRates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PriceRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ad_account_id')
                    ->label('Ad Account (optional)')
                    ->relationship('adAccount', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('min_usd')
                    ->label('Min USD')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('dollar_rate')
                    ->label('Dollar Rate (BDT per USD)')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
            ])
            ->columns(1);
    }
}
