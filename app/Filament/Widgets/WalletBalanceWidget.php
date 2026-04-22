<?php

namespace App\Filament\Widgets;

use App\Models\Wallet;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WalletBalanceWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $balance = Wallet::query()
            ->where('user_id', Filament::auth()->id())
            ->value('balance') ?? 0;

        return [
            Stat::make('Current Wallet Balance', 'BDT '.number_format((float) $balance, 2)),
        ];
    }
}
