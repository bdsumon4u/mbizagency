<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderSource: string implements HasColor, HasIcon, HasLabel
{
    case USER = 'user';
    case ADMIN = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::ADMIN => 'Admin',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::USER => 'heroicon-o-user',
            self::ADMIN => 'heroicon-o-building-office',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::USER => 'gray',
            self::ADMIN => 'success',
        };
    }
}
