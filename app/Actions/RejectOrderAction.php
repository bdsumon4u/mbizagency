<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Admin;
use App\Models\Order;
use App\Models\WalletTransaction;
use App\Services\AdAccountSpendCapService;
use App\Services\FacebookAdAccountService;
use Illuminate\Support\Facades\DB;
use Throwable;

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

            $wasApproved = $lockedOrder->status === OrderStatus::APPROVED;

            $adAccount = $lockedOrder->adAccount()
                ->lockForUpdate()
                ->firstOrFail();

            $oldSpendCap = $adAccount->spend_cap;

            if ($wasApproved) {
                try {
                    if (! $adAccount->synced_at?->isAfter(now()->subMinutes(5))) {
                        app(FacebookAdAccountService::class)->syncSingleAdAccount($adAccount);
                        $adAccount->refresh();
                        $oldSpendCap = $adAccount->spend_cap;
                    }
                } catch (Throwable $exception) {
                    report($exception);
                }

                $adAccount->decrement('spend_cap', $lockedOrder->usd_amount * 100);
                $adAccount->refresh();

                app(AdAccountSpendCapService::class)->sync($adAccount);
                app(FacebookAdAccountService::class)->syncSingleAdAccount($adAccount);

                if ($lockedOrder->payment_source === 'wallet') {
                    $user = $lockedOrder->user;
                    $totalPayable = $lockedOrder->bdt_amount + $lockedOrder->processing_fee;

                    $user->wallet_balance += $totalPayable;
                    $user->save();

                    WalletTransaction::create([
                        'user_id' => $user->id,
                        'type' => WalletTransactionType::REFUND,
                        'amount' => $totalPayable,
                        'status' => WalletTransactionStatus::APPROVED,
                        'balance_after' => $user->wallet_balance,
                        'ad_account_id' => $adAccount->id,
                        'usd_amount' => -$lockedOrder->usd_amount,
                        'dollar_rate' => $lockedOrder->dollar_rate,
                        'approved_at' => now(),
                    ]);
                }
            }

            $lockedOrder->update([
                'admin_id' => $admin?->id,
                'status' => OrderStatus::REJECTED,
                'balance' => $adAccount->spend_cap - $adAccount->amount_spent,
                'old_limit' => $oldSpendCap,
                'new_limit' => $adAccount->spend_cap,
            ]);

            return $lockedOrder->refresh();
        });
    }
}
