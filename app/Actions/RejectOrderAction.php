<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class RejectOrderAction
{
    public function __invoke(Order $order, ?Admin $admin = null): Order
    {
        return DB::transaction(function () use ($order, $admin): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status === OrderStatus::REJECTED) {
                return $lockedOrder;
            }

            $lockedOrder->update([
                'admin_id' => $admin?->id,
                'status' => OrderStatus::REJECTED,
            ]);

            return $lockedOrder->refresh();
        });
    }
}
