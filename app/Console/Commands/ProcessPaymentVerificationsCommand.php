<?php

namespace App\Console\Commands;

use App\Enums\PaymentVerificationStatus;
use App\Models\PaymentVerification;
use App\Services\PaymentVerificationEngine;
use Illuminate\Console\Command;

class ProcessPaymentVerificationsCommand extends Command
{
    protected $signature = 'payment-verification:process';

    protected $description = 'Evaluate pending payment verifications against cooling-off window and incoming Telegram SMS.';

    public function handle(PaymentVerificationEngine $engine): int
    {
        $pendingVerifications = PaymentVerification::query()
            ->where('verification_status', PaymentVerificationStatus::Pending)
            ->with(['verifiable', 'incomingSms'])
            ->get();

        if ($pendingVerifications->isEmpty()) {
            $this->info('No pending payment verifications to evaluate.');

            return self::SUCCESS;
        }

        $this->info("Evaluating {$pendingVerifications->count()} pending verification(s)...");

        foreach ($pendingVerifications as $verification) {
            $verifiableType = class_basename((string) $verification->verifiable_type);
            $this->line("Processing {$verifiableType} #{$verification->verifiable_id} (Verification #{$verification->id})...");

            $evaluated = $engine->evaluate($verification);

            $this->info(" -> Status: {$evaluated->verification_status->value} | Reason: {$evaluated->status_reason}");
        }

        return self::SUCCESS;
    }
}
