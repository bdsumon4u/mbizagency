<?php

namespace App\Services;

use App\Models\IncomingSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookService
{
    public function __construct(
        private SmsParserService $smsParser,
    ) {}

    public function isValidSecret(Request $request): bool
    {
        $configuredSecret = config('payment_verification.telegram.webhook_secret');

        if (empty($configuredSecret)) {
            return true;
        }

        $incomingHeader = $request->header('X-Telegram-Bot-Api-Secret-Token');

        return hash_equals((string) $configuredSecret, (string) $incomingHeader);
    }

    public function isAllowedChat(string|int|null $chatId): bool
    {
        $allowed = config('payment_verification.telegram.allowed_chat_ids', []);

        if (empty($allowed)) {
            return true;
        }

        return in_array((string) $chatId, array_map('strval', $allowed), true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handlePayload(array $payload): ?IncomingSms
    {
        $messageData = $payload['channel_post'] ?? $payload['message'] ?? null;

        if (! is_array($messageData)) {
            return null;
        }

        $rawText = trim((string) ($messageData['text'] ?? $messageData['caption'] ?? ''));

        if ($rawText === '') {
            return null;
        }

        $chatId = $messageData['chat']['id'] ?? null;

        if (! $this->isAllowedChat($chatId)) {
            Log::warning('Telegram message received from unauthorized chat ID: '.$chatId);

            return null;
        }

        $telegramMessageId = (string) ($messageData['message_id'] ?? '');

        // Check if message was already ingested to avoid duplicate ingestion from Telegram retries
        $existing = IncomingSms::query()
            ->where('telegram_message_id', $telegramMessageId)
            ->where('channel_or_chat_id', (string) $chatId)
            ->first();

        if ($existing instanceof IncomingSms) {
            return $existing;
        }

        $parsed = $this->smsParser->parse($rawText);

        return IncomingSms::create([
            'source' => 'telegram',
            'telegram_message_id' => $telegramMessageId,
            'channel_or_chat_id' => (string) $chatId,
            'raw_message' => $rawText,
            'provider' => $parsed['provider'],
            'sender_title' => $parsed['sender_title'],
            'trx_id' => $parsed['trx_id'],
            'amount' => $parsed['amount'],
            'balance' => $parsed['balance'],
            'fee' => $parsed['fee'],
            'sender_account' => $parsed['sender_account'],
            'receiver_account' => $parsed['receiver_account'],
            'reference' => $parsed['reference'],
            'txn_type' => $parsed['txn_type'],
            'transaction_time' => $parsed['transaction_time'] ?? now(),
            'metadata' => [
                'update_id' => $payload['update_id'] ?? null,
                'chat' => $messageData['chat'] ?? null,
                'date' => $messageData['date'] ?? null,
            ],
        ]);
    }
}
