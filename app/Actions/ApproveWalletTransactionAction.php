<?php

namespace App\Actions;

use App\Enums\WalletTransactionStatus;
use App\Models\Admin;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class ApproveWalletTransactionAction
{
    public function __invoke(WalletTransaction $transaction, ?Admin $admin = null): WalletTransaction
    {
        return DB::transaction(function () use ($transaction, $admin): WalletTransaction {
            $lockedTransaction = WalletTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransaction->status === WalletTransactionStatus::APPROVED) {
                return $lockedTransaction;
            }

            $user = $lockedTransaction->user()
                ->lockForUpdate()
                ->firstOrFail();

            $user->wallet_balance += $lockedTransaction->amount;
            $user->save();

            $lockedTransaction->update([
                'status' => WalletTransactionStatus::APPROVED,
                'admin_id' => $admin?->id,
                'approved_at' => now(),
                'balance_after' => $user->wallet_balance,
            ]);

            return $lockedTransaction->refresh();
        });
    }
}
