<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class OrderHistoryStatsWidget extends Widget
{
    protected static ?int $sort = -3;

    protected string $view = 'filament.widgets.order-history-stats-widget';

    protected int|string|array $columnSpan = 'full';

    public bool $showOtherStats = false;

    public function getStats(): array
    {
        $user = Filament::auth()->user();
        $isAdmin = Filament::getCurrentPanel()?->getId() === 'admin';

        $query = Order::query()
            ->when(! $isAdmin, function ($query) use ($user) {
                return $query->whereBelongsTo($user);
            });

        $pendingDepositUsd = (clone $query)->where('status', OrderStatus::PENDING)->sum('usd_amount');
        $pendingDepositBdt = (clone $query)->where('status', OrderStatus::PENDING)->sum('bdt_amount');

        $todayDepositUsd = (clone $query)
            ->where('status', OrderStatus::APPROVED)
            ->whereDate('created_at', now()->toDateString())
            ->sum('usd_amount');
        $todayDepositBdt = (clone $query)
            ->where('status', OrderStatus::APPROVED)
            ->whereDate('created_at', now()->toDateString())
            ->sum('bdt_amount');

        $yesterdayDepositUsd = (clone $query)
            ->where('status', OrderStatus::APPROVED)
            ->whereDate('created_at', now()->subDay()->toDateString())
            ->sum('usd_amount');
        $yesterdayDepositBdt = (clone $query)
            ->where('status', OrderStatus::APPROVED)
            ->whereDate('created_at', now()->subDay()->toDateString())
            ->sum('bdt_amount');

        $thisMonthDepositUsd = (clone $query)
            ->where('status', OrderStatus::APPROVED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('usd_amount');
        $thisMonthDepositBdt = (clone $query)
            ->where('status', OrderStatus::APPROVED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('bdt_amount');

        $lastMonth = now()->startOfMonth()->subMonth();
        $lastMonthDepositUsd = (clone $query)
            ->where('status', OrderStatus::APPROVED)
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('usd_amount');
        $lastMonthDepositBdt = (clone $query)
            ->where('status', OrderStatus::APPROVED)
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('bdt_amount');

        return [
            [
                'key' => 'pending',
                'label' => 'Pending Deposit',
                'colspan' => 2,
                'value' => '$'.number_format($pendingDepositUsd, 2),
                'bdt_value' => '৳'.number_format($pendingDepositBdt, 2),
                'subtext' => 'Awaiting Approval',
                'icon' => 'heroicon-o-clock',
                'icon_color' => 'text-orange-500',
                'icon_bg' => 'bg-orange-50',
            ],
            [
                'key' => 'today',
                'label' => 'Today',
                'colspan' => 2,
                'value' => '$'.number_format($todayDepositUsd, 2),
                'bdt_value' => '৳'.number_format($todayDepositBdt, 2),
                'subtext' => now()->format('M d, Y'),
                'icon' => 'heroicon-o-bolt',
                'icon_color' => 'text-indigo-500',
                'icon_bg' => 'bg-indigo-50',
            ],
            [
                'key' => 'yesterday',
                'label' => 'Yesterday',
                'colspan' => 2,
                'value' => '$'.number_format($yesterdayDepositUsd, 2),
                'bdt_value' => '৳'.number_format($yesterdayDepositBdt, 2),
                'subtext' => now()->subDay()->format('M d, Y'),
                'icon' => 'heroicon-o-calendar',
                'icon_color' => 'text-teal-500',
                'icon_bg' => 'bg-teal-50',
            ],
            [
                'key' => 'this_month',
                'label' => 'This Month',
                'colspan' => 3,
                'value' => '$'.number_format($thisMonthDepositUsd, 2),
                'bdt_value' => '৳'.number_format($thisMonthDepositBdt, 2),
                'subtext' => now()->format('F Y'),
                'icon' => 'heroicon-o-calendar',
                'icon_color' => 'text-blue-500',
                'icon_bg' => 'bg-blue-50',
            ],
            [
                'key' => 'last_month',
                'label' => 'Last Month',
                'colspan' => 3,
                'value' => '$'.number_format($lastMonthDepositUsd, 2),
                'bdt_value' => '৳'.number_format($lastMonthDepositBdt, 2),
                'subtext' => $lastMonth->format('F Y'),
                'icon' => 'heroicon-o-calendar',
                'icon_color' => 'text-purple-500',
                'icon_bg' => 'bg-purple-50',
            ],
        ];
    }
}
