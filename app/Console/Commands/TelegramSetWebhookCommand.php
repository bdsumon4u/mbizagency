<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook {url? : Custom webhook URL to register}';

    protected $description = 'Register the Telegram Bot webhook with Telegram API';

    public function handle(): int
    {
        $botToken = config('payment_verification.telegram.bot_token');
        $secretToken = config('payment_verification.telegram.webhook_secret');

        if (empty($botToken)) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in your .env file.');

            return self::FAILURE;
        }

        $webhookUrl = $this->argument('url') ?: url('/api/webhooks/telegram/sms');

        $this->info("Registering Telegram webhook to: {$webhookUrl}");

        $payload = [
            'url' => $webhookUrl,
            'allowed_updates' => ['message', 'channel_post'],
        ];

        if (! empty($secretToken)) {
            $payload['secret_token'] = $secretToken;
        }

        $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", $payload);

        if ($response->successful() && ($response->json('ok') === true)) {
            $this->info('Webhook successfully registered with Telegram!');
            $this->line('Telegram response: '.$response->json('description', 'OK'));

            return self::SUCCESS;
        }

        $this->error('Failed to register webhook: '.$response->body());

        return self::FAILURE;
    }
}
