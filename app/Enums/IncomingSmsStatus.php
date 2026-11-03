<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum IncomingSmsStatus: string implements HasColor, HasIcon, HasLabel
{
    case Unmatched = 'unmatched';
    case Matched = 'matched';
    case Ignored = 'ignored';
    case Duplicate = 'duplicate';

    public function getLabel(): string
    {
        return match ($this) {
            self::Unmatched => 'Unmatched',
            self::Matched => 'Matched',
            self::Ignored => 'Ignored',
            self::Duplicate => 'Duplicate',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Unmatched => 'heroicon-o-arrow-path',
            self::Matched => 'heroicon-o-check-circle',
            self::Ignored => 'heroicon-o-minus-circle',
            self::Duplicate => 'heroicon-o-exclamation-triangle',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unmatched => 'warning',
            self::Matched => 'success',
            self::Ignored => 'gray',
            self::Duplicate => 'danger',
        };
    }
}
