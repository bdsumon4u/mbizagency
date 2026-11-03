<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PaymentVerificationStatus;
use App\Models\PaymentVerification;
use App\Services\PaymentVerificationEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ProcessPendingPaymentVerificationsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function handle(PaymentVerificationEngine $engine): void
    {
        $pendingVerifications = PaymentVerification::query()
            ->where('verification_status', PaymentVerificationStatus::Pending)
            ->with(['verifiable', 'incomingSms'])
            ->get();

        foreach ($pendingVerifications as $verification) {
            try {
                $engine->evaluate($verification);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
