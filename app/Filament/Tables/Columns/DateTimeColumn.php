<?php

declare(strict_types=1);

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;

final class DateTimeColumn extends TextColumn
{
    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->date('d-M-Y')
            ->alignCenter()
            ->description(function (Column $column) {
                $value = $column->getState();

                if (! $value) {
                    return null;
                }

                return $value->format('h:i A');
            });
    }
}
