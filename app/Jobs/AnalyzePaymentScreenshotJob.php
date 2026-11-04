<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\PaymentVerificationStatus;
use App\Models\Order;
use App\Models\PaymentVerification;
use App\Models\WalletTransaction;
use App\Services\GeminiVisionService;
use App\Services\PaymentVerificationEngine;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class AnalyzePaymentScreenshotJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * @param  class-string<Order|WalletTransaction>  $verifiableType
     */
    public function __construct(
        public string $verifiableType,
        public int $verifiableId,
    ) {}

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(
        GeminiVisionService $geminiService,
        PaymentVerificationEngine $verificationEngine,
    ): void {
        /** @var Order|WalletTransaction|null $verifiable */
        $verifiable = $this->verifiableType::query()->find($this->verifiableId);

        if (! $verifiable) {
            return;
        }

        $screenshots = $verifiable->screenshots;
        if (empty($screenshots) || ! is_array($screenshots)) {
            Log::info("No screenshots to analyze for {$this->verifiableType} #{$this->verifiableId}");

            return;
        }

        // Take primary screenshot
        $primaryScreenshot = is_string($screenshots[0]) ? $screenshots[0] : null;
        if (! $primaryScreenshot) {
            return;
        }

        $aiResult = $geminiService->analyzeScreenshot($primaryScreenshot);

        $transactionTime = null;
        if (! empty($aiResult['transaction_time'])) {
            try {
                $transactionTime = Carbon::parse($aiResult['transaction_time'], config('app.timezone'));
            } catch (\Throwable) {
                // Ignore parse errors
            }
        }

        $verification = PaymentVerification::query()->firstOrNew(
            [
                'verifiable_type' => $this->verifiableType,
                'verifiable_id' => $this->verifiableId,
            ]
        );

        if ($verification->verification_status === PaymentVerificationStatus::Approved) {
            return;
        }

        $verification->fill([
            'screenshot_path' => $primaryScreenshot,
            'provider' => $aiResult['provider'],
            'trx_id' => $aiResult['trx_id'],
            'reference' => $aiResult['reference'] ?? null,
            'amount' => $aiResult['amount'],
            'fee' => $aiResult['fee'] ?? null,
            'total_amount' => $aiResult['total_amount'] ?? null,
            'balance' => $aiResult['balance'] ?? null,
            'txn_type' => $aiResult['txn_type'] ?? null,
            'beneficiary_bank' => $aiResult['beneficiary_bank'] ?? null,
            'sender_account' => $aiResult['sender_account'] ?? $aiResult['sender_number'],
            'sender_name' => $aiResult['sender_name'] ?? null,
            'receiver_account' => $aiResult['receiver_account'] ?? $aiResult['receiver_number'],
            'receiver_name' => $aiResult['receiver_name'] ?? null,
            'transaction_time' => $transactionTime,
            'confidence_score' => $aiResult['confidence_score'],
            'is_suspicious' => $aiResult['is_suspicious_or_edited'],
            'ai_response' => $aiResult['raw_response'] ?? $aiResult,
            'verification_status' => PaymentVerificationStatus::Pending,
            'status_reason' => 'Screenshot analyzed by Gemini. Ready for matching evaluation.',
            'checked_at' => now(),
        ]);
        $verification->save();

        // Evaluate immediately with verification engine
        $verificationEngine->evaluate($verification);
    }
}
