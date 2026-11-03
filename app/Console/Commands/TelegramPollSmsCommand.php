<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPendingPaymentVerificationsJob;
use App\Services\TelegramWebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramPollSmsCommand extends Command
{
    protected $signature = 'telegram:poll-sms {--once : Run only a single polling loop and exit}';

    protected $description = 'Poll Telegram getUpdates API for incoming SMS (useful for local development without webhook)';

    public function handle(TelegramWebhookService $telegramService): int
    {
        $botToken = config('payment_verification.telegram.bot_token');

        if (empty($botToken)) {
            $this->error('TELEGRAM_BOT_TOKEN is not configured in .env.');

            return self::FAILURE;
        }

        $this->info('Starting Telegram update polling... Press Ctrl+C to stop.');

        $offset = 0;

        do {
            try {
                $response = Http::timeout(40)->get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                    'offset' => $offset,
                    'timeout' => 30,
                    'allowed_updates' => json_encode(['message', 'channel_post']),
                ]);

                if ($response->successful() && $response->json('ok') === true) {
                    $updates = $response->json('result', []);

                    foreach ($updates as $update) {
                        $updateId = (int) $update['update_id'];
                        $offset = $updateId + 1;

                        $sms = $telegramService->handlePayload($update);

                        if ($sms) {
                            $this->info("Ingested SMS #{$sms->id} | Provider: {$sms->provider} | TrxID: {$sms->trx_id} | Amount: {$sms->amount} BDT");
                            ProcessPendingPaymentVerificationsJob::dispatch();
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->warn('Polling error: '.$e->getMessage());
                sleep(2);
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
