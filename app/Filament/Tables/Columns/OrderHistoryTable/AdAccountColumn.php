<?php

namespace App\Filament\Tables\Columns\OrderHistoryTable;

use Filament\Tables\Columns\Column;

class AdAccountColumn extends Column
{
    protected string $view = 'filament.tables.columns.order-history-table.ad-account-column';

    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->disabledClick(true);
    }
}
