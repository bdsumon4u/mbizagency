<?php

use App\Enums\PaymentVerificationStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Jobs\AnalyzePaymentScreenshotJob;
use App\Models\PaymentVerification;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                                    'fee' => 0.0,
                                    'total_amount' => 3000.00,
                                    'sender_number' => '01711223344',
                                    'receiver_number' => '01899887766',
                                    'reference' => 'deposit',
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

    $verification = PaymentVerification::where('verifiable_type', WalletTransaction::class)
        ->where('verifiable_id', $walletTx->id)
        ->first();

    expect($verification)->not->toBeNull()
        ->and($verification->trx_id)->toBe('BKA99112233')
        ->and($verification->provider)->toBe('bkash')
        ->and((float) $verification->amount)->toBe(3000.00)
        ->and((float) $verification->confidence_score)->toBe(0.98)
        ->and($verification->is_suspicious)->toBeFalse()
        ->and($verification->verification_status)->toBe(PaymentVerificationStatus::Pending);
});
