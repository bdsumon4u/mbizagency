<?php

use App\Enums\IncomingSmsStatus;
use App\Enums\PaymentVerificationStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\ProcessPendingPaymentVerificationsJob;
use App\Models\IncomingSms;
use App\Models\PaymentVerification;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PaymentVerificationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('http webhook accepts incoming sms json and stores it with parsed fields', function () {
    Queue::fake();
    Config::set('payment_verification.webhook.secret_key', 'my-secret-key-123');

    $payload = [
        'id' => 'msg_1001',
        'from' => 'bKash',
        'text' => 'You have received Tk 3,500.00 from 01711223344. Fee Tk 0.00. Balance Tk 45,000.00. TrxID BK99HTTP88 at 03/11/2026 14:15',
        'timestamp' => 1730643300,
        'sim' => 'SIM1',
    ];

    $response = $this->postJson('/api/webhooks/sms', $payload, [
        'X-API-Key' => 'my-secret-key-123',
    ]);

    $response->assertSuccessful();
    $response->assertJsonPath('ok', true)
        ->assertJsonPath('source', 'http_webhook')
        ->assertJsonPath('provider', 'bkash')
        ->assertJsonPath('trx_id', 'BK99HTTP88')
        ->assertJsonPath('amount', 3500);

    $this->assertDatabaseHas('incoming_sms', [
        'source' => 'http_webhook',
        'external_id' => 'msg_1001',
        'provider' => 'bkash',
        'trx_id' => 'BK99HTTP88',
        'amount' => 3500.00,
        'status' => IncomingSmsStatus::Unmatched->value,
    ]);

    Queue::assertPushed(ProcessPendingPaymentVerificationsJob::class);
});

test('http webhook accepts authorization via bearer token and webhook-secret header', function () {
    Queue::fake();
    Config::set('payment_verification.webhook.secret_key', 'secret-abc');

    // Test with Bearer Token
    $response1 = $this->postJson('/api/webhooks/sms', [
        'message' => 'Received Tk 1,000.00 TrxID TXN111',
    ], [
        'Authorization' => 'Bearer secret-abc',
    ]);
    $response1->assertSuccessful();

    // Test with X-Webhook-Secret header
    $response2 = $this->postJson('/api/webhooks/sms', [
        'message' => 'Received Tk 2,000.00 TrxID TXN222',
    ], [
        'X-Webhook-Secret' => 'secret-abc',
    ]);
    $response2->assertSuccessful();

    // Test with query parameter
    $response3 = $this->postJson('/api/webhooks/sms?api_key=secret-abc', [
        'message' => 'Received Tk 3,000.00 TrxID TXN333',
    ]);
    $response3->assertSuccessful();
});

test('http webhook rejects unauthorized request when secret is wrong', function () {
    Config::set('payment_verification.webhook.secret_key', 'correct-secret');

    $response = $this->postJson('/api/webhooks/sms', [
        'message' => 'Test message',
    ], [
        'X-API-Key' => 'wrong-secret',
    ]);

    $response->assertStatus(401);
});

test('http webhook is idempotent and prevents duplicate ingestion', function () {
    Queue::fake();
    Config::set('payment_verification.webhook.secret_key', null);

    $payload = [
        'id' => 'device_sms_555',
        'sender' => 'bKash',
        'message' => 'You have received Tk 1,200.00 from 01800000000. TrxID IDEM9988 at 03/11/2026 12:00',
    ];

    $res1 = $this->postJson('/api/webhooks/sms', $payload);
    $res1->assertSuccessful();

    $res2 = $this->postJson('/api/webhooks/sms', $payload);
    $res2->assertSuccessful();

    expect(IncomingSms::where('external_id', 'device_sms_555')->count())->toBe(1);
});

test('full pipeline: http webhook triggers auto-approval for matched wallet deposit', function () {
    Config::set('payment_verification.window_time_minutes', 5);
    Config::set('payment_verification.auto_approve', true);
    Config::set('payment_verification.webhook.secret_key', null);

    $user = User::factory()->create(['wallet_balance' => 0.00]);

    // Pending wallet deposit created 6 minutes ago
    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 4500.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(6),
    ]);

    // AI screenshot verification created 6 minutes ago
    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'trx_id' => 'RESTHOOK777',
        'amount' => 4500.00,
        'confidence_score' => 0.98,
        'is_suspicious' => false,
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(6),
    ]);

    // SMS arrives directly via HTTP REST API Webhook
    $response = $this->postJson('/api/webhooks/sms', [
        'sender' => 'bKash',
        'text' => 'You have received Tk 4,500.00 from 01711223344. Fee Tk 0.00. Balance Tk 60,000.00. TrxID RESTHOOK777 at 03/11/2026 15:30',
    ]);

    $response->assertSuccessful();

    // Run verification engine
    $engine = app(PaymentVerificationEngine::class);
    $result = $engine->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::Approved)
        ->and($walletTx->refresh()->status)->toBe(WalletTransactionStatus::APPROVED)
        ->and((float) $user->refresh()->wallet_balance)->toEqual(4500.00);

    $sms = IncomingSms::where('trx_id', 'RESTHOOK777')->first();
    expect($sms)->not->toBeNull()
        ->and($sms->source)->toBe('http_webhook')
        ->and($sms->status)->toBe(IncomingSmsStatus::Matched);
});
