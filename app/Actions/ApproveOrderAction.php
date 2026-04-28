<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Services\AdAccountSpendCapService;
use Illuminate\Support\Facades\DB;

class ApproveOrderAction
{
    public function __invoke(Order $order, ?Admin $admin = null): Order
    {
        return DB::transaction(function () use ($order, $admin): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status === OrderStatus::APPROVED) {
                return $lockedOrder;
            }

            $adAccount = $lockedOrder->adAccount()
                ->lockForUpdate()
                ->firstOrFail();

            $adAccount->increment('spend_cap', $lockedOrder->usd_amount * 100);

            app(AdAccountSpendCapService::class)->sync($adAccount->refresh());

            $lockedOrder->update([
                'admin_id' => $admin?->id,
                'status' => OrderStatus::APPROVED,
                'new_limit' => $adAccount->spend_cap,
                'approved_at' => now(),
            ]);

            return $lockedOrder->refresh();
        });
    }
}
