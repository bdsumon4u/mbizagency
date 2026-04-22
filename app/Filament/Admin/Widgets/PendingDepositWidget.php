<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingDepositWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $pendingAmount = Transaction::query()
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->where('status', Transaction::STATUS_PENDING)
            ->sum('amount');

        return [
            Stat::make('Total Pending Deposit Amount', 'BDT '.number_format((float) $pendingAmount, 2)),
        ];
    }
}
