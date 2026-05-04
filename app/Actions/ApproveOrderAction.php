<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Services\AdAccountSpendCapService;
use App\Services\FacebookAdAccountService;
use Illuminate\Support\Facades\DB;
use Throwable;

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

            try {
                if (! $adAccount->synced_at?->isAfter(now()->subMinutes(5))) {
                    app(FacebookAdAccountService::class)->syncSingleAdAccount($adAccount);
                    $adAccount->refresh();
                }
            } catch (Throwable $exception) {
                report($exception);
            }

            app(FacebookAdAccountService::class)->syncSingleAdAccount($adAccount);
            $oldSpendCap = $adAccount->spend_cap;
            $lockedOrder->update(['balance' => $oldSpendCap - $adAccount->amount_spent]);
            $adAccount->increment('spend_cap', $lockedOrder->usd_amount * 100);
            $adAccount->refresh();

            app(AdAccountSpendCapService::class)->sync($adAccount);
            app(FacebookAdAccountService::class)->syncSingleAdAccount($adAccount);

            $lockedOrder->update([
                'admin_id' => $admin?->id,
                'status' => OrderStatus::APPROVED,
                'old_limit' => $oldSpendCap,
                'new_limit' => $adAccount->spend_cap,
                'approved_at' => now(),
            ]);

            return $lockedOrder->refresh();
        });
    }
}
