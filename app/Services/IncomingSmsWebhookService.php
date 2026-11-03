<?php

namespace App\Services;

use App\Enums\IncomingSmsStatus;
use App\Models\IncomingSms;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IncomingSmsWebhookService
{
    public function __construct(
        private SmsParserService $smsParser,
    ) {}

    public function isValidSecret(Request $request): bool
    {
        $configuredKey = config('payment_verification.webhook.secret_key');

        if (empty($configuredKey)) {
            return true;
        }

        $provided = $request->header('X-API-Key')
            ?? $request->header('X-Webhook-Secret')
            ?? $request->bearerToken()
            ?? $request->query('api_key')
            ?? $request->query('secret')
            ?? $request->input('secret')
            ?? $request->input('api_key');

        if (! is_string($provided)) {
            return false;
        }

        return hash_equals((string) $configuredKey, $provided);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handlePayload(array $payload): ?IncomingSms
    {
        $rawMessage = trim((string) (
            $payload['message']
            ?? $payload['text']
            ?? $payload['body']
            ?? $payload['content']
            ?? $payload['msg']
            ?? $payload['sms']
            ?? ''
        ));

        if ($rawMessage === '') {
            return null;
        }

        $senderTitle = trim((string) (
            $payload['sender']
            ?? $payload['from']
            ?? $payload['sender_title']
            ?? $payload['phone']
            ?? $payload['address']
            ?? ''
        ));

        $externalId = trim((string) (
            $payload['id']
            ?? $payload['sms_id']
            ?? $payload['message_id']
            ?? $payload['external_id']
            ?? ''
        ));

        // Idempotency check 1: by external_id if provided
        if ($externalId !== '') {
            $existing = IncomingSms::query()
                ->where('external_id', $externalId)
                ->first();

            if ($existing instanceof IncomingSms) {
                return $existing;
            }
        }

        // Idempotency check 2: same message text received within the last 10 minutes
        $recentDuplicate = IncomingSms::query()
            ->where('raw_message', $rawMessage)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->first();

        if ($recentDuplicate instanceof IncomingSms) {
            return $recentDuplicate;
        }

        // Parse message
        $parsed = $this->smsParser->parse($rawMessage);

        // Resolve transaction time
        $transactionTime = null;
        $timestamp = $payload['timestamp'] ?? $payload['sent_stamp'] ?? $payload['date'] ?? $payload['time'] ?? null;
        if (! empty($timestamp)) {
            try {
                if (is_numeric($timestamp)) {
                    // Handle millisecond or second Unix timestamp
                    $numericTimestamp = (int) $timestamp;
                    $transactionTime = $numericTimestamp > 9999999999
                        ? Carbon::createFromTimestampMs($numericTimestamp)
                        : Carbon::createFromTimestamp($numericTimestamp);
                } else {
                    $transactionTime = Carbon::parse((string) $timestamp);
                }
            } catch (\Throwable) {
                // Ignore parse errors, fallback to parsed time or now
            }
        }

        $finalTransactionTime = $transactionTime ?? $parsed['transaction_time'] ?? now();

        return IncomingSms::create([
            'source' => 'http_webhook',
            'status' => IncomingSmsStatus::Unmatched,
            'external_id' => $externalId !== '' ? $externalId : null,
            'raw_message' => $rawMessage,
            'provider' => $payload['provider'] ?? $parsed['provider'],
            'sender_title' => $senderTitle !== '' ? $senderTitle : $parsed['sender_title'],
            'trx_id' => $payload['trx_id'] ?? $parsed['trx_id'],
            'amount' => isset($payload['amount']) && is_numeric($payload['amount']) ? (float) $payload['amount'] : $parsed['amount'],
            'balance' => isset($payload['balance']) && is_numeric($payload['balance']) ? (float) $payload['balance'] : $parsed['balance'],
            'fee' => isset($payload['fee']) && is_numeric($payload['fee']) ? (float) $payload['fee'] : $parsed['fee'],
            'sender_account' => $payload['sender_account'] ?? $parsed['sender_account'],
            'receiver_account' => $payload['receiver_account'] ?? $parsed['receiver_account'],
            'reference' => $payload['reference'] ?? $parsed['reference'],
            'txn_type' => $payload['txn_type'] ?? $parsed['txn_type'],
            'transaction_time' => $finalTransactionTime,
            'metadata' => [
                'headers' => request()?->headers->all() ?? [],
                'payload' => $payload,
            ],
        ]);
    }
}
