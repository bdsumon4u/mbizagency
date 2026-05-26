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

    public function getStats(): array
    {
        $user = Filament::auth()->user();
        $isAdmin = Filament::getCurrentPanel()?->getId() === 'admin';

        $query = Order::query()
            ->when(! $isAdmin, function ($query) use ($user) {
                return $query->whereBelongsTo($user);
            });

        $pendingDeposit = (clone $query)->where('status', OrderStatus::PENDING)->sum('usd_amount');
        $approvedDeposit = (clone $query)->where('status', OrderStatus::APPROVED)->sum('usd_amount');
        $thisMonthDeposit = (clone $query)
            ->where('status', OrderStatus::APPROVED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('usd_amount');
        $todayDeposit = (clone $query)
            ->where('status', OrderStatus::APPROVED)
            ->whereDate('created_at', now()->toDateString())
            ->sum('usd_amount');

        return [
            [
                'label' => 'Approved Deposit',
                'value' => '$'.number_format($approvedDeposit, 2),
                'subtext' => 'Ready to Use',
                'icon' => 'heroicon-o-check-circle',
                'icon_color' => 'text-green-500',
                'icon_bg' => 'bg-green-50',
            ],
            [
                'label' => 'Pending Deposit',
                'value' => '$'.number_format($pendingDeposit, 2),
                'subtext' => 'Awaiting Approval',
                'icon' => 'heroicon-o-clock',
                'icon_color' => 'text-orange-500',
                'icon_bg' => 'bg-orange-50',
            ],
            [
                'label' => 'This Month',
                'value' => '$'.number_format($thisMonthDeposit, 2),
                'subtext' => now()->format('F Y'),
                'icon' => 'heroicon-o-calendar',
                'icon_color' => 'text-blue-500',
                'icon_bg' => 'bg-blue-50',
            ],
            [
                'label' => 'Today',
                'value' => '$'.number_format($todayDeposit, 2),
                'subtext' => now()->format('M d, Y'),
                'icon' => 'heroicon-o-bolt',
                'icon_color' => 'text-indigo-500',
                'icon_bg' => 'bg-indigo-50',
            ],
        ];
    }
}
