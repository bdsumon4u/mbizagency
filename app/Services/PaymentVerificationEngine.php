<?php

namespace App\Services;

use App\Actions\ApproveOrderAction;
use App\Actions\ApproveWalletTransactionAction;
use App\Enums\IncomingSmsStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentVerificationStatus;
use App\Enums\WalletTransactionStatus;
use App\Models\IncomingSms;
use App\Models\Order;
use App\Models\PaymentVerification;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentVerificationEngine
{
    public function __construct(
        private ApproveOrderAction $approveOrderAction,
        private ApproveWalletTransactionAction $approveWalletTransactionAction,
        private SmsParserService $smsParser,
    ) {}

    /**
     * Process verification for a given PaymentVerification record.
     */
    public function evaluate(PaymentVerification $verification): PaymentVerification
    {
        $verifiable = $verification->verifiable;

        if (! ($verifiable instanceof Order) && ! ($verifiable instanceof WalletTransaction)) {
            $verification->update([
                'verification_status' => PaymentVerificationStatus::ManualReview,
                'status_reason' => 'Target order or wallet transaction record no longer exists or is invalid.',
                'checked_at' => now(),
            ]);

            return $verification->refresh();
        }

        // 1. Check if already processed or not pending
        if ($verification->verification_status !== PaymentVerificationStatus::Pending) {
            return $verification;
        }

        // 2. Check if verifiable itself is already approved or rejected
        if ($this->isVerifiableAlreadyResolved($verifiable)) {
            $verification->update([
                'checked_at' => now(),
                'status_reason' => 'Transaction was already manually resolved.',
            ]);

            return $verification->refresh();
        }

        // 3. Check Cooling-Off Window Time (Anti-Fraud / Race Condition Guard)
        $windowMinutes = (int) config('payment_verification.window_time_minutes', 5);
        $createdAt = $verifiable->created_at ?? $verification->created_at ?? now();
        $elapsedMinutes = $createdAt->diffInMinutes(now());

        if ($elapsedMinutes < $windowMinutes) {
            $remaining = $windowMinutes - $elapsedMinutes;
            $verification->update([
                'checked_at' => now(),
                'status_reason' => "Cooling-off window active ({$remaining} min remaining to detect duplicate claims).",
            ]);

            return $verification->refresh();
        }

        // 4. Validate AI extraction quality and suspicion flag
        if ($verification->is_suspicious) {
            $verification->update([
                'verification_status' => PaymentVerificationStatus::Suspicious,
                'status_reason' => 'AI flagged screenshot as potentially edited, fraudulent, or inconsistent.',
                'checked_at' => now(),
            ]);

            return $verification->refresh();
        }

        $confidenceThreshold = (float) config('payment_verification.confidence_threshold', 0.85);
        if ($verification->confidence_score !== null && (float) $verification->confidence_score < $confidenceThreshold) {
            $verification->update([
                'verification_status' => PaymentVerificationStatus::ManualReview,
                'status_reason' => "AI confidence score ({$verification->confidence_score}) is below the required threshold ({$confidenceThreshold}).",
                'checked_at' => now(),
            ]);

            return $verification->refresh();
        }

        $expectedPayable = $this->calculateExpectedPayable($verifiable);

        // 5. Anti-Fraud Replay Check: If TrxID is present, has it ALREADY been approved?
        $trxId = $verification->trx_id ?: $verification->reference;
        if (! empty($trxId)) {
            $alreadyApproved = PaymentVerification::query()
                ->where(function ($q) use ($trxId) {
                    $q->where('trx_id', $trxId)->orWhere('reference', $trxId);
                })
                ->where('id', '!=', $verification->id)
                ->where('verification_status', PaymentVerificationStatus::Approved)
                ->first();

            if ($alreadyApproved) {
                $verification->update([
                    'verification_status' => PaymentVerificationStatus::Suspicious,
                    'status_reason' => "FRAUD / REPLAY ALERT: TrxID {$trxId} was already approved on {$alreadyApproved->verifiable_type} #{$alreadyApproved->verifiable_id}!",
                    'checked_at' => now(),
                ]);

                return $verification->refresh();
            }

            // Duplicate Pending Check for same TrxID
            $conflictingPending = PaymentVerification::query()
                ->where(function ($q) use ($trxId) {
                    $q->where('trx_id', $trxId)->orWhere('reference', $trxId);
                })
                ->where('id', '!=', $verification->id)
                ->where('verification_status', PaymentVerificationStatus::Pending)
                ->get();

            if ($conflictingPending->isNotEmpty()) {
                $allIds = $conflictingPending->pluck('id')->push($verification->id)->implode(', ');
                $reason = "DUPLICATE CLAIM CONFLICT: Multiple pending submissions (Verifications #{$allIds}) claim TrxID {$trxId}. Held for manual review.";

                $verification->update([
                    'verification_status' => PaymentVerificationStatus::ManualReview,
                    'status_reason' => $reason,
                    'checked_at' => now(),
                ]);

                PaymentVerification::query()
                    ->whereIn('id', $conflictingPending->pluck('id'))
                    ->update([
                        'verification_status' => PaymentVerificationStatus::ManualReview,
                        'status_reason' => $reason,
                        'checked_at' => now(),
                    ]);

                return $verification->refresh();
            }
        }

        // 6. Find matching Incoming SMS (Dual Mode: TrxID/Ref match OR Bank Multi-Factor match)
        $matchingSms = $this->findMatchingSms($verification, $verifiable, $expectedPayable);

        if (! $matchingSms) {
            $maxWaitMinutes = (int) config('payment_verification.max_wait_time_minutes', 60);
            if ($elapsedMinutes > $maxWaitMinutes) {
                $verification->update([
                    'verification_status' => PaymentVerificationStatus::ManualReview,
                    'status_reason' => "No matching Telegram SMS received within {$maxWaitMinutes} minutes.",
                    'checked_at' => now(),
                ]);

                return $verification->refresh();
            }

            $searchHint = $trxId ? "TrxID {$trxId}" : "amount {$expectedPayable} BDT";
            $verification->update([
                'status_reason' => "Waiting for matching Telegram SMS for {$searchHint}.",
                'checked_at' => now(),
            ]);

            return $verification->refresh();
        }

        // 7. Check if this SMS was ALREADY claimed/matched to another record
        if ($matchingSms->status === IncomingSmsStatus::Matched) {
            $verification->update([
                'incoming_sms_id' => $matchingSms->id,
                'verification_status' => PaymentVerificationStatus::Suspicious,
                'status_reason' => "This SMS was already matched and claimed by {$matchingSms->matched_type} #{$matchingSms->matched_id}.",
                'checked_at' => now(),
            ]);

            return $verification->refresh();
        }

        // 8. Check for DUPLICATE CLAIMS on the same matching SMS
        $otherVerificationsForSameSms = PaymentVerification::query()
            ->where('incoming_sms_id', $matchingSms->id)
            ->where('id', '!=', $verification->id)
            ->get();

        if ($otherVerificationsForSameSms->isNotEmpty()) {
            $allIds = $otherVerificationsForSameSms->pluck('id')->push($verification->id)->implode(', ');
            $reason = "DUPLICATE CLAIM CONFLICT: Multiple submissions (Verifications #{$allIds}) matched the same SMS #{$matchingSms->id}. Held for manual review.";

            $verification->update([
                'incoming_sms_id' => $matchingSms->id,
                'verification_status' => PaymentVerificationStatus::ManualReview,
                'status_reason' => $reason,
                'checked_at' => now(),
            ]);

            PaymentVerification::query()
                ->whereIn('id', $otherVerificationsForSameSms->pluck('id'))
                ->update([
                    'verification_status' => PaymentVerificationStatus::ManualReview,
                    'status_reason' => $reason,
                    'checked_at' => now(),
                ]);

            return $verification->refresh();
        }

        // 9. Amount Validation
        $smsAmount = (float) $matchingSms->amount;
        if (abs($expectedPayable - $smsAmount) > 0.01) {
            $verification->update([
                'incoming_sms_id' => $matchingSms->id,
                'verification_status' => PaymentVerificationStatus::ManualReview,
                'status_reason' => "Amount mismatch: expected payable is {$expectedPayable} BDT, but Telegram SMS is {$smsAmount} BDT.",
                'checked_at' => now(),
            ]);

            return $verification->refresh();
        }

        // 10. Auto-Approve Check
        if (! config('payment_verification.auto_approve', true)) {
            $verification->update([
                'incoming_sms_id' => $matchingSms->id,
                'verification_status' => PaymentVerificationStatus::ManualReview,
                'status_reason' => 'All criteria matched, but auto-approve is disabled in configuration. Please approve manually.',
                'checked_at' => now(),
            ]);

            return $verification->refresh();
        }

        // 11. Atomic Execution & Approval
        return $this->executeApproval($verification, $matchingSms, $verifiable, $smsAmount);
    }

    /**
     * Finds matching SMS using either TrxID/Ref match or Bank Multi-Factor match.
     */
    private function findMatchingSms(
        PaymentVerification $verification,
        Order|WalletTransaction $verifiable,
        float $expectedPayable,
    ): ?IncomingSms {
        $trxId = $verification->trx_id ?: $verification->reference;

        // 1. Explicit TrxID / Ref match (primary for bKash, Nagad, Rocket, or whenever bank SMS has TrxID)
        if (! empty($trxId)) {
            $sms = IncomingSms::query()
                ->where(function ($q) use ($trxId) {
                    $q->where('trx_id', $trxId)->orWhere('reference', $trxId);
                })
                ->where('status', IncomingSmsStatus::Unmatched)
                ->first();

            if ($sms) {
                return $sms;
            }
        }

        // 2. Bank Multi-Factor Match: Provider + Masked Account + Amount
        // Full account can come from screenshot or paymentMethod on the verifiable
        $fullAccount = $verification->receiver_account
            ?: $verifiable->paymentMethod?->account_number;

        if (empty($fullAccount)) {
            return null;
        }

        $provider = $verification->provider ?: $this->resolveProviderFromPaymentMethod($verifiable);

        $candidates = IncomingSms::query()
            ->where('status', IncomingSmsStatus::Unmatched)
            ->where(function ($q) use ($expectedPayable) {
                $q->whereBetween('amount', [$expectedPayable - 0.01, $expectedPayable + 0.01]);
            })
            ->when($provider, function ($q) use ($provider) {
                $q->where(function ($sub) use ($provider) {
                    $sub->where('provider', $provider)
                        ->orWhere('provider', 'bank')
                        ->orWhereNull('provider');
                });
            })
            ->get();

        $matchedCandidates = [];
        foreach ($candidates as $candidate) {
            if ($candidate->receiver_account && $this->smsParser->matchesMaskedAccount($fullAccount, $candidate->receiver_account)) {
                $matchedCandidates[] = $candidate;
            }
        }

        // If multiple distinct SMS match the same bank account and amount, hold for manual review to prevent ambiguity!
        if (count($matchedCandidates) > 1) {
            $ids = collect($matchedCandidates)->pluck('id')->implode(', ');
            $verification->update([
                'verification_status' => PaymentVerificationStatus::ManualReview,
                'status_reason' => "Ambiguity: Multiple incoming bank SMS (#{$ids}) match account and amount ({$expectedPayable} BDT). Manual review required.",
                'checked_at' => now(),
            ]);

            return null;
        }

        return $matchedCandidates[0] ?? null;
    }

    private function resolveProviderFromPaymentMethod(Order|WalletTransaction $verifiable): ?string
    {
        $name = mb_strtolower($verifiable->paymentMethod?->name ?? '');

        if (str_contains($name, 'city')) {
            return 'city_bank';
        }
        if (str_contains($name, 'dbbl') || str_contains($name, 'dutch')) {
            return 'dbbl';
        }
        if (str_contains($name, 'brac')) {
            return 'brac_bank';
        }
        if (str_contains($name, 'ebl') || str_contains($name, 'eastern')) {
            return 'ebl';
        }
        if (str_contains($name, 'bkash')) {
            return 'bkash';
        }
        if (str_contains($name, 'nagad')) {
            return 'nagad';
        }
        if (str_contains($name, 'rocket')) {
            return 'rocket';
        }

        return null;
    }

    private function isVerifiableAlreadyResolved(Order|WalletTransaction $verifiable): bool
    {
        if ($verifiable instanceof Order) {
            return $verifiable->status !== OrderStatus::PENDING;
        }

        if ($verifiable instanceof WalletTransaction) {
            return $verifiable->status !== WalletTransactionStatus::PENDING;
        }

        return false;
    }

    private function calculateExpectedPayable(Order|WalletTransaction $verifiable): float
    {
        if ($verifiable instanceof Order) {
            return round((float) $verifiable->bdt_amount + (float) $verifiable->processing_fee, 2);
        }

        if ($verifiable instanceof WalletTransaction) {
            return round((float) $verifiable->payable_amount, 2);
        }

        return 0.0;
    }

    private function executeApproval(
        PaymentVerification $verification,
        IncomingSms $sms,
        Order|WalletTransaction $verifiable,
        float $amount,
    ): PaymentVerification {
        return DB::transaction(function () use ($verification, $sms, $verifiable, $amount): PaymentVerification {
            // Lock verifiable and SMS
            $lockedSms = IncomingSms::query()
                ->whereKey($sms->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSms->status !== IncomingSmsStatus::Unmatched) {
                $verification->update([
                    'verification_status' => PaymentVerificationStatus::ManualReview,
                    'status_reason' => 'Incoming SMS status changed concurrently. Manual review required.',
                    'checked_at' => now(),
                ]);

                return $verification->refresh();
            }

            if ($verifiable instanceof Order) {
                $lockedOrder = Order::query()->whereKey($verifiable->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== OrderStatus::PENDING) {
                    return $verification->refresh();
                }

                ($this->approveOrderAction)($lockedOrder, null);
            } elseif ($verifiable instanceof WalletTransaction) {
                $lockedWalletTx = WalletTransaction::query()->whereKey($verifiable->id)->lockForUpdate()->firstOrFail();
                if ($lockedWalletTx->status !== WalletTransactionStatus::PENDING) {
                    return $verification->refresh();
                }

                ($this->approveWalletTransactionAction)($lockedWalletTx, null);
            }

            // Update SMS
            $lockedSms->update([
                'status' => IncomingSmsStatus::Matched,
                'matched_type' => get_class($verifiable),
                'matched_id' => $verifiable->id,
                'matched_at' => now(),
            ]);

            // Update Verification
            $matchId = $sms->trx_id ?: $sms->receiver_account ?: "SMS #{$sms->id}";
            $verification->update([
                'incoming_sms_id' => $lockedSms->id,
                'verification_status' => PaymentVerificationStatus::Approved,
                'verified_at' => now(),
                'checked_at' => now(),
                'status_reason' => "Verified & Approved automatically: Matched Telegram SMS ({$matchId}, {$amount} BDT) after cooling-off window.",
            ]);

            Log::info("PaymentVerificationEngine: Successfully auto-approved {$verifiable->getMorphClass()} #{$verifiable->id} with SMS #{$sms->id}.");

            return $verification->refresh();
        });
    }
}
