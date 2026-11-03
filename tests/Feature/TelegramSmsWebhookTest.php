<?php

use App\Jobs\ProcessPendingPaymentVerificationsJob;
use App\Models\IncomingSms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('webhook accepts telegram channel post with bkash sms and stores it', function () {
    Queue::fake();
    Config::set('payment_verification.telegram.webhook_secret', 'test-secret-123');

    $payload = [
        'update_id' => 99182371,
        'channel_post' => [
            'message_id' => 4501,
            'chat' => [
                'id' => -100192837465,
                'title' => 'Forwarded SMS Channel',
                'type' => 'channel',
            ],
            'date' => 1730198400,
            'text' => 'You have received Tk 2,000.00 from 01711223344. Fee Tk 0.00. Balance Tk 25,000.00. TrxID BK99AA77XX at 29/10/2026 14:15',
        ],
    ];

    $response = $this->postJson('/api/webhooks/telegram/sms', $payload, [
        'X-Telegram-Bot-Api-Secret-Token' => 'test-secret-123',
    ]);

    $response->assertSuccessful();
    $response->assertJsonPath('ok', true)
        ->assertJsonPath('ingested', true)
        ->assertJsonPath('trx_id', 'BK99AA77XX');

    $this->assertDatabaseHas('incoming_sms', [
        'telegram_message_id' => '4501',
        'channel_or_chat_id' => '-100192837465',
        'provider' => 'bkash',
        'trx_id' => 'BK99AA77XX',
        'amount' => 2000.00,
        'status' => 'unmatched',
    ]);

    Queue::assertPushed(ProcessPendingPaymentVerificationsJob::class);
});

test('webhook rejects unauthorized request when secret does not match', function () {
    Config::set('payment_verification.telegram.webhook_secret', 'correct-secret');

    $response = $this->postJson('/api/webhooks/telegram/sms', [], [
        'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
    ]);

    $response->assertStatus(401);
});

test('webhook is idempotent on duplicate telegram update delivery', function () {
    Queue::fake();
    Config::set('payment_verification.telegram.webhook_secret', null);

    $payload = [
        'update_id' => 112233,
        'message' => [
            'message_id' => 5001,
            'chat' => [
                'id' => 888777,
                'type' => 'private',
            ],
            'text' => 'Money Received: Tk 1,000.00 from 01700000000 is successful. TxnID: 88AABBCC. Date: 29/10/2026 15:00',
        ],
    ];

    $this->postJson('/api/webhooks/telegram/sms', $payload)->assertSuccessful();
    $this->postJson('/api/webhooks/telegram/sms', $payload)->assertSuccessful();

    expect(IncomingSms::where('telegram_message_id', '5001')->count())->toBe(1);
});
