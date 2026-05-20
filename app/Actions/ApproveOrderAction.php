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
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
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

            $oldSpendCap = $adAccount->spend_cap;
            $adAccount->increment('spend_cap', $lockedOrder->usd_amount * 100);
            $adAccount->refresh();

            app(AdAccountSpendCapService::class)->sync($adAccount);
            app(FacebookAdAccountService::class)->syncSingleAdAccount($adAccount);

            if ($lockedOrder->payment_source === 'wallet') {
                $user = $lockedOrder->user;
                $totalPayable = $lockedOrder->bdt_amount + $lockedOrder->processing_fee;
                if ($user->wallet_balance < $totalPayable) {
                    Notification::make()
                        ->title('Insufficient wallet balance to approve this order.')
                        ->danger()
                        ->send();

                    throw new Halt;
                }

                $user->wallet_balance -= $totalPayable;
                $user->save();

                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => WalletTransactionType::AD_ACCOUNT_DEPOSIT,
                    'amount' => -$totalPayable,
                    'status' => WalletTransactionStatus::APPROVED,
                    'balance_after' => $user->wallet_balance,
                    'ad_account_id' => $adAccount->id,
                    'usd_amount' => $lockedOrder->usd_amount,
                    'dollar_rate' => $lockedOrder->dollar_rate,
                    'approved_at' => now(),
                ]);
            }

            $lockedOrder->update([
                'admin_id' => $admin?->id,
                'status' => OrderStatus::APPROVED,
                'balance' => $adAccount->spend_cap - $adAccount->amount_spent,
                'old_limit' => $oldSpendCap,
                'new_limit' => $adAccount->spend_cap,
                'approved_at' => now(),
            ]);

            return $lockedOrder->refresh();
        });
    }
}
