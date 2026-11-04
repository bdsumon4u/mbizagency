<?php

use App\Enums\PaymentVerificationStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\AnalyzePaymentScreenshotJob;
use App\Models\PaymentVerification;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\GeminiVisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('job calls gemini and stores structured payment verification', function () {
    Storage::fake('public');
    Config::set('payment_verification.gemini.api_key', 'test-gemini-key');
    Config::set('payment_verification.gemini.model', 'gemini-2.5-flash');

    // Store a fake screenshot file
    $screenshotPath = 'wallet/screenshots/test_proof.jpg';
    Storage::disk('public')->put($screenshotPath, 'fake-image-binary-data');

    $user = User::factory()->create();
    $walletTx = WalletTransaction::create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 3000.00,
        'status' => WalletTransactionStatus::PENDING,
        'screenshots' => [$screenshotPath],
    ]);

    // Mock Gemini API
    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'provider' => 'bkash',
                                    'trx_id' => 'BKA99112233',
                                    'amount' => 3000.00,
                                    'fee' => 12.50,
                                    'total_amount' => 3012.50,
                                    'balance' => 4000.00,
                                    'txn_type' => 'Bank Transfer',
                                    'beneficiary_bank' => 'Eastern Bank PLC',
                                    'sender_number' => '01711223344',
                                    'sender_name' => 'Customer Name',
                                    'receiver_number' => '01899887766',
                                    'receiver_name' => 'Agency Account',
                                    'reference' => 'REF123456',
                                    'transaction_time' => '2026-10-29 15:30:00',
                                    'status_in_screenshot' => 'successful',
                                    'is_suspicious_or_edited' => false,
                                    'confidence_score' => 0.98,
                                    'notes' => 'Genuine bKash confirmation receipt.',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $job = new AnalyzePaymentScreenshotJob(WalletTransaction::class, $walletTx->id);
    app()->call([$job, 'handle']);

    Http::assertSent(fn (ClientRequest $request): bool => $request->hasHeader('x-goog-api-key', 'test-gemini-key')
        && ! str_contains($request->url(), 'key='));

    $verification = PaymentVerification::where('verifiable_type', WalletTransaction::class)
        ->where('verifiable_id', $walletTx->id)
        ->first();

    expect($verification)->not->toBeNull()
        ->and($verification->trx_id)->toBe('BKA99112233')
        ->and($verification->receiver_name)->toBe('Agency Account')
        ->and($verification->sender_name)->toBe('Customer Name')
        ->and($verification->sender_account)->toBe('01711223344')
        ->and($verification->receiver_account)->toBe('01899887766')
        ->and($verification->reference)->toBe('REF123456')
        ->and($verification->provider)->toBe('bkash')
        ->and((float) $verification->amount)->toBe(3000.00)
        ->and((float) $verification->fee)->toBe(12.50)
        ->and((float) $verification->total_amount)->toBe(3012.50)
        ->and((float) $verification->balance)->toBe(4000.00)
        ->and($verification->txn_type)->toBe('Bank Transfer')
        ->and($verification->beneficiary_bank)->toBe('Eastern Bank PLC')
        ->and($verification->transaction_time->format('Y-m-d H:i:s'))->toBe('2026-10-29 15:30:00')
        ->and((float) $verification->confidence_score)->toBe(0.98)
        ->and($verification->is_suspicious)->toBeFalse()
        ->and($verification->verification_status)->toBe(PaymentVerificationStatus::Pending);
});

test('normalizes City and BRAC references as transaction ids while keeping EBL reference distinct', function () {
    Storage::fake('public');
    Config::set('payment_verification.gemini.api_key', 'test-gemini-key');
    Config::set('payment_verification.gemini.model', 'gemini-3.5-flash-lite');

    foreach (['city', 'brac', 'ebl'] as $screenshot) {
        Storage::disk('public')->put("payments/{$screenshot}.jpg", 'fake-image-binary-data');
    }

    $geminiResponse = static fn (array $data): array => [
        'candidates' => [[
            'content' => [
                'parts' => [[
                    'text' => json_encode($data),
                ]],
            ],
        ]],
    ];

    Http::fake([
        'https://generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($geminiResponse([
                'provider' => 'city_bank',
                'reference' => '100019139250',
                'sender_account' => '1502964672001',
                'receiver_account' => '1503915274001',
                'transaction_time' => '2026-10-29 03:07:00',
            ]), 200)
            ->push($geminiResponse([
                'provider' => 'brac_bank',
                'reference' => 'S50100626',
                'sender_account' => '2079008100001',
                'receiver_account' => '2053944080001',
                'transaction_time' => '2026-10-29 14:22:39',
            ]), 200)
            ->push($geminiResponse([
                'provider' => 'ebl',
                'reference' => '1278008263020055',
                'sender_account' => 'Acc 127144***6497',
                'receiver_account' => 'Acc 107107***6337',
                'transaction_time' => '2026-10-29 07:04:00',
            ]), 200),
    ]);

    $service = app(GeminiVisionService::class);
    $city = $service->analyzeScreenshot('payments/city.jpg');
    $brac = $service->analyzeScreenshot('payments/brac.jpg');
    $ebl = $service->analyzeScreenshot('payments/ebl.jpg');

    expect($city['trx_id'])->toBe('100019139250')
        ->and($city['reference'])->toBe('100019139250')
        ->and($city['sender_account'])->toBe('1502964672001')
        ->and($city['receiver_account'])->toBe('1503915274001')
        ->and($brac['trx_id'])->toBe('S50100626')
        ->and($brac['reference'])->toBe('S50100626')
        ->and($brac['sender_account'])->toBe('2079008100001')
        ->and($brac['receiver_account'])->toBe('2053944080001')
        ->and($ebl['trx_id'])->toBeNull()
        ->and($ebl['reference'])->toBe('1278008263020055')
        ->and($ebl['sender_account'])->toBe('127144***6497')
        ->and($ebl['receiver_account'])->toBe('107107***6337');
});
