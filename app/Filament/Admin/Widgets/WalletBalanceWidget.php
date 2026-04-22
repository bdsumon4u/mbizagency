<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WalletBalanceWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalBalance = Wallet::query()->sum('balance');

        return [
            Stat::make('Current Total Wallet Balance', 'BDT '.number_format((float) $totalBalance, 2)),
        ];
    }
}
