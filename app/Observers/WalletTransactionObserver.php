<?php

namespace App\Observers;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\AnalyzePaymentScreenshotJob;
use App\Models\WalletTransaction;

class WalletTransactionObserver
{
    public function created(WalletTransaction $transaction): void
    {
        if (
            $transaction->type === WalletTransactionType::DEPOSIT
            && $transaction->status === WalletTransactionStatus::PENDING
            && ! empty($transaction->screenshots)
        ) {
            AnalyzePaymentScreenshotJob::dispatch(WalletTransaction::class, $transaction->id);
        }
    }
}
