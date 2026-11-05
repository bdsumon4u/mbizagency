<?php

use App\Enums\IncomingSmsStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentVerificationStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\IncomingSms;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentVerification;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\PaymentVerificationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('holds transaction in pending status while cooling-off window is active', function () {
    Config::set('payment_verification.window_time_minutes', 5);

    $user = User::factory()->create();
    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 1500.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(2), // 2 minutes ago, window is 5 minutes
    ]);

    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'trx_id' => 'TRX123456',
        'amount' => 1500.00,
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(2),
    ]);

    IncomingSms::create([
        'trx_id' => 'TRX123456',
        'amount' => 1500.00,
        'raw_message' => 'Received Tk 1,500.00 TrxID TRX123456',
        'status' => IncomingSmsStatus::Unmatched,
    ]);

    $engine = app(PaymentVerificationEngine::class);
    $result = $engine->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::Pending)
        ->and($result->status_reason)->toContain('Cooling-off window active')
        ->and($walletTx->refresh()->status)->toBe(WalletTransactionStatus::PENDING);
});

test('detects duplicate claims when multiple pending transactions claim the same trx id', function () {
    Config::set('payment_verification.window_time_minutes', 5);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $tx1 = WalletTransaction::create([
        'user_id' => $user1->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 1000.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    $tx2 = WalletTransaction::create([
        'user_id' => $user2->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 1000.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    $v1 = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $tx1->id,
        'trx_id' => 'SHAREDTRX99',
        'amount' => 1000.00,
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    $v2 = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $tx2->id,
        'trx_id' => 'SHAREDTRX99',
        'amount' => 1000.00,
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    IncomingSms::create([
        'trx_id' => 'SHAREDTRX99',
        'amount' => 1000.00,
        'raw_message' => 'Received Tk 1,000.00 TrxID SHAREDTRX99',
        'status' => IncomingSmsStatus::Unmatched,
    ]);

    $engine = app(PaymentVerificationEngine::class);
    $result1 = $engine->evaluate($v1);

    expect($result1->verification_status)->toBe(PaymentVerificationStatus::ManualReview)
        ->and($result1->status_reason)->toContain('DUPLICATE CLAIM CONFLICT')
        ->and($v2->refresh()->verification_status)->toBe(PaymentVerificationStatus::ManualReview)
        ->and($tx1->refresh()->status)->toBe(WalletTransactionStatus::PENDING)
        ->and($tx2->refresh()->status)->toBe(WalletTransactionStatus::PENDING);
});

test('flags fraud replay attack when a trx id was already approved', function () {
    Config::set('payment_verification.window_time_minutes', 5);

    $user = User::factory()->create();

    // Past approved verification
    PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => 999,
        'trx_id' => 'USEDTRX77',
        'amount' => 500.00,
        'verification_status' => PaymentVerificationStatus::Approved,
    ]);

    $newTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 500.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    $newVerification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $newTx->id,
        'trx_id' => 'USEDTRX77',
        'amount' => 500.00,
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    $engine = app(PaymentVerificationEngine::class);
    $result = $engine->evaluate($newVerification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::Suspicious)
        ->and($result->status_reason)->toContain('FRAUD / REPLAY ALERT')
        ->and($newTx->refresh()->status)->toBe(WalletTransactionStatus::PENDING);
});

test('auto-approves wallet deposit after cooling-off window when sms matches exactly', function () {
    Config::set('payment_verification.window_time_minutes', 5);
    Config::set('payment_verification.auto_approve', true);

    $user = User::factory()->create(['wallet_balance' => 0.00]);

    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 2500.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(6), // 6 minutes passed
    ]);

    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'trx_id' => 'CLEANTRX888',
        'amount' => 2500.00,
        'verification_status' => PaymentVerificationStatus::Pending,
        'confidence_score' => 0.95,
        'is_suspicious' => false,
        'created_at' => now()->subMinutes(6),
    ]);

    $sms = IncomingSms::create([
        'trx_id' => 'CLEANTRX888',
        'amount' => 2500.00,
        'raw_message' => 'Received Tk 2,500.00 TrxID CLEANTRX888',
        'status' => IncomingSmsStatus::Unmatched,
    ]);

    $engine = app(PaymentVerificationEngine::class);
    $result = $engine->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::Approved)
        ->and($result->incoming_sms_id)->toBe($sms->id)
        ->and($walletTx->refresh()->status)->toBe(WalletTransactionStatus::APPROVED)
        ->and($user->refresh()->wallet_balance)->toEqual(2500.00)
        ->and($sms->refresh()->status)->toBe(IncomingSmsStatus::Matched);
});

test('holds transaction as manual review if sms amount mismatches', function () {
    Config::set('payment_verification.window_time_minutes', 5);

    $user = User::factory()->create();

    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 5000.00, // Order payable is 5000
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'trx_id' => 'MISMATCHTRX1',
        'amount' => 5000.00,
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    IncomingSms::create([
        'trx_id' => 'MISMATCHTRX1',
        'amount' => 1000.00, // But SMS is only 1000!
        'raw_message' => 'Received Tk 1,000.00 TrxID MISMATCHTRX1',
        'status' => IncomingSmsStatus::Unmatched,
    ]);

    $engine = app(PaymentVerificationEngine::class);
    $result = $engine->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::ManualReview)
        ->and($result->status_reason)->toContain('Amount mismatch')
        ->and($walletTx->refresh()->status)->toBe(WalletTransactionStatus::PENDING);
});

test('auto-approves city bank citytouch deposit matching masked account and amount', function () {
    Config::set('payment_verification.window_time_minutes', 5);
    Config::set('payment_verification.auto_approve', true);

    $user = User::factory()->create(['wallet_balance' => 0.00]);

    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 6400.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    // Extracted from Citytouch screenshot
    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'provider' => 'city_bank',
        'receiver_account' => '1503915274001',
        'receiver_name' => 'CYBER 32',
        'reference' => '100019139250',
        'amount' => 6400.00,
        'confidence_score' => 0.96,
        'is_suspicious' => false,
        'ai_response' => [
            'status_in_screenshot' => 'unknown', // Even if status is unknown, no sign of failure means it is accepted
        ],
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    // Forwarded City Bank SMS
    $sms = IncomingSms::create([
        'provider' => 'city_bank',
        'amount' => 6400.00,
        'receiver_account' => '1503***4001',
        'balance' => 135318.00,
        'raw_message' => "29-Oct-2026\nCITYTOUCH TXN\nTk. 6,400 Deposit\nTk. 1,35,318 Balance\nA/C: 1503***4001",
        'status' => IncomingSmsStatus::Unmatched,
    ]);

    $engine = app(PaymentVerificationEngine::class);
    $result = $engine->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::Approved)
        ->and($result->incoming_sms_id)->toBe($sms->id)
        ->and($walletTx->refresh()->status)->toBe(WalletTransactionStatus::APPROVED)
        ->and($user->refresh()->wallet_balance)->toEqual(6400.00)
        ->and($sms->refresh()->status)->toBe(IncomingSmsStatus::Matched);
});

test('auto-approves bkash to bank transfer to city bank matching incoming bank sms', function () {
    Config::set('payment_verification.window_time_minutes', 5);
    Config::set('payment_verification.auto_approve', true);

    $user = User::factory()->create(['wallet_balance' => 0.00]);

    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 7000.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    // Extracted from bKash to Bank screenshot
    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'provider' => 'city_bank', // Normalized to City Bank because money is received by City Bank
        'beneficiary_bank' => 'সিটি ব্যাংক',
        'receiver_account' => '1503XXXXXX001',
        'receiver_name' => 'Cyber 32',
        'trx_id' => 'DK4528OF9R',
        'amount' => 7000.00,
        'fee' => 87.50,
        'total_amount' => 7087.50,
        'confidence_score' => 0.98,
        'is_suspicious' => false,
        'verification_status' => PaymentVerificationStatus::Pending,
        'transaction_time' => now()->subMinutes(8),
        'created_at' => now()->subMinutes(10),
    ]);

    // Forwarded City Bank credit notification SMS
    $sms = IncomingSms::create([
        'provider' => 'city_bank',
        'amount' => 7000.00,
        'receiver_account' => '1503***4001',
        'balance' => 142318.00,
        'raw_message' => "04-Nov-2026\nCITYTOUCH TXN\nTk. 7,000 Deposit\nTk. 1,42,318 Balance\nA/C: 1503***4001",
        'transaction_time' => now()->subMinutes(8),
        'status' => IncomingSmsStatus::Unmatched,
    ]);

    $engine = app(PaymentVerificationEngine::class);
    $result = $engine->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::Approved)
        ->and($result->incoming_sms_id)->toBe($sms->id)
        ->and($walletTx->refresh()->status)->toBe(WalletTransactionStatus::APPROVED)
        ->and($user->refresh()->wallet_balance)->toEqual(7000.00)
        ->and($sms->refresh()->status)->toBe(IncomingSmsStatus::Matched);
});

test('auto-approves dbbl atm crm cash deposit matching masked account and amount', function () {
    Config::set('payment_verification.window_time_minutes', 5);
    Config::set('payment_verification.auto_approve', true);

    $user = User::factory()->create(['wallet_balance' => 0.00]);

    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 8000.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    // Extracted from DBBL CRM paper slip
    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'provider' => 'dbbl',
        'receiver_account' => '1641100042219',
        'trx_id' => '00906359',
        'amount' => 8000.00,
        'confidence_score' => 0.94,
        'is_suspicious' => false,
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    // Forwarded DBBL ATM SMS
    $sms = IncomingSms::create([
        'provider' => 'dbbl',
        'amount' => 8000.00,
        'receiver_account' => '***2219',
        'balance' => 9691.61,
        'txn_type' => 'CRM/CDM Cash Deposit On-Us',
        'raw_message' => 'Dear Sir, your A/C ***2219 credited CRM/CDM Cash Deposit On-Us by Tk8,000.00 on 28-10-2026 09:54:59 PM C/B Tk9,691.61. NexusPay',
        'status' => IncomingSmsStatus::Unmatched,
    ]);

    $engine = app(PaymentVerificationEngine::class);
    $result = $engine->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::Approved)
        ->and($result->incoming_sms_id)->toBe($sms->id)
        ->and($walletTx->refresh()->status)->toBe(WalletTransactionStatus::APPROVED)
        ->and($user->refresh()->wallet_balance)->toEqual(8000.00)
        ->and($sms->refresh()->status)->toBe(IncomingSmsStatus::Matched);
});

test('auto-approves brac bank deposit matching masked account 20539**0001 and amount 12600 BDT', function () {
    Config::set('payment_verification.window_time_minutes', 5);
    Config::set('payment_verification.auto_approve', true);

    $user = User::factory()->create(['wallet_balance' => 0.00]);

    // Active agency BRAC Bank payment method
    PaymentMethod::create([
        'name' => 'BRAC Bank',
        'type' => 'Bank',
        'account_number' => '2053944080001',
        'is_active' => true,
    ]);

    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 12600.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    // Extracted from screenshot by Gemini
    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'provider' => 'brac_bank',
        'amount' => 12600.00,
        'confidence_score' => 0.95,
        'is_suspicious' => false,
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    // Incoming BRAC Bank SMS from production sample
    $sms = IncomingSms::create([
        'provider' => 'brac_bank',
        'amount' => 12600.00,
        'receiver_account' => '20539**0001',
        'balance' => 868662.85,
        'raw_message' => 'TK 12,600.00 has been credited to your A/C# 20539**0001 on 03-11-26. Your A/C balance is TK 8,68,662.85. For Enquiry call: 16221',
        'status' => IncomingSmsStatus::Unmatched,
    ]);

    $engine = app(PaymentVerificationEngine::class);
    $result = $engine->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::Approved)
        ->and($result->incoming_sms_id)->toBe($sms->id)
        ->and($walletTx->refresh()->status)->toBe(WalletTransactionStatus::APPROVED)
        ->and($user->refresh()->wallet_balance)->toEqual(12600.00)
        ->and($sms->refresh()->status)->toBe(IncomingSmsStatus::Matched);
});

test('does not match a transaction sms outside the configured screenshot time window', function () {
    Config::set('payment_verification.window_time_minutes', 5);
    Config::set('payment_verification.sms_match_window_minutes', 5);

    $user = User::factory()->create();
    $transactionTime = now()->subMinutes(8);
    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 2000.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);
    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'trx_id' => 'OLDTXN123',
        'transaction_time' => $transactionTime,
        'confidence_score' => 0.98,
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);
    IncomingSms::create([
        'trx_id' => 'OLDTXN123',
        'amount' => 2000.00,
        'transaction_time' => $transactionTime->copy()->subMinutes(6),
        'raw_message' => 'Received Tk 2,000.00 TrxID OLDTXN123',
        'status' => IncomingSmsStatus::Unmatched,
    ]);

    $result = app(PaymentVerificationEngine::class)->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::Pending)
        ->and($result->incoming_sms_id)->toBeNull()
        ->and($walletTx->refresh()->status)->toBe(WalletTransactionStatus::PENDING);
});

test('marks manual review when screenshot status explicitly indicates failure', function () {
    $user = User::factory()->create();
    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 1000.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'amount' => 1000.00,
        'confidence_score' => 0.95,
        'ai_response' => [
            'status_in_screenshot' => 'failed',
        ],
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    $result = app(PaymentVerificationEngine::class)->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::ManualReview)
        ->and($result->status_reason)->toContain('Screenshot indicates transaction failed (failed)');
});

test('marks manual review with clear message when ai analysis encounters an error', function () {
    $user = User::factory()->create();
    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 1000.00,
        'status' => WalletTransactionStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    $verification = PaymentVerification::create([
        'verifiable_type' => WalletTransaction::class,
        'verifiable_id' => $walletTx->id,
        'confidence_score' => 0.0,
        'ai_response' => [
            'status_in_screenshot' => 'error',
            'notes' => 'Gemini API returned HTTP 503.',
        ],
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    $result = app(PaymentVerificationEngine::class)->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::ManualReview)
        ->and($result->status_reason)->toContain('Gemini API returned HTTP 503');
});

test('evaluates payment verification on an order without missing attribute exception', function () {
    Http::fake([
        'https://graph.facebook.com/*' => Http::response(['success' => true], 200),
    ]);

    Config::set('payment_verification.window_time_minutes', 5);
    Config::set('payment_verification.auto_approve', true);

    $user = User::factory()->create();
    $bm = BusinessManager::query()->create([
        'bm_id' => '11111',
        'access_token' => 'token_secret',
        'name' => 'BM 1',
    ]);
    $adAccount = AdAccount::query()->create([
        'business_manager_id' => $bm->id,
        'user_id' => $user->id,
        'act_id' => '12345',
        'name' => 'Account 1',
    ]);

    $order = Order::create([
        'user_id' => $user->id,
        'ad_account_id' => $adAccount->id,
        'usd_amount' => 100.00,
        'dollar_rate' => 125.00,
        'bdt_amount' => 12500.00,
        'processing_fee' => 0.00,
        'status' => OrderStatus::PENDING,
        'created_at' => now()->subMinutes(10),
    ]);

    $verification = PaymentVerification::create([
        'verifiable_type' => Order::class,
        'verifiable_id' => $order->id,
        'provider' => 'city_bank',
        'receiver_account' => '1503915274001',
        'receiver_name' => 'CYBER 32',
        'reference' => '100019139250',
        'amount' => 12500.00,
        'confidence_score' => 0.98,
        'is_suspicious' => false,
        'verification_status' => PaymentVerificationStatus::Pending,
        'created_at' => now()->subMinutes(10),
    ]);

    $sms = IncomingSms::create([
        'provider' => 'city_bank',
        'amount' => 12500.00,
        'receiver_account' => '1503***4001',
        'raw_message' => "CITYTOUCH TXN\nTk. 12,500 Deposit\nA/C: 1503***4001",
        'status' => IncomingSmsStatus::Unmatched,
    ]);

    $engine = app(PaymentVerificationEngine::class);
    $result = $engine->evaluate($verification);

    expect($result->verification_status)->toBe(PaymentVerificationStatus::Approved)
        ->and($result->incoming_sms_id)->toBe($sms->id)
        ->and($order->refresh()->status)->toBe(OrderStatus::APPROVED);
});
