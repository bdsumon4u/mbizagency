<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;

class CurrencyColumn extends TextColumn
{
    public static function make(?string $name = null, ?string $currency = 'USD'): static
    {
        return parent::make($name)
            ->money(fn (Column $column): string => $column->getRecord()->currency ?? $currency)
            ->alignCenter();
    }
}
