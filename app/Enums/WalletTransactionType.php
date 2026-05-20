<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum WalletTransactionType: string implements HasColor, HasIcon, HasLabel
{
    case DEPOSIT = 'deposit';
    case AD_ACCOUNT_DEPOSIT = 'ad_account_deposit';

    public function getLabel(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Wallet Deposit',
            self::AD_ACCOUNT_DEPOSIT => 'Ad Account Deposit',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::DEPOSIT => 'heroicon-o-arrow-down-tray',
            self::AD_ACCOUNT_DEPOSIT => 'heroicon-o-arrow-up-right',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DEPOSIT => 'success',
            self::AD_ACCOUNT_DEPOSIT => 'warning',
        };
    }
}
