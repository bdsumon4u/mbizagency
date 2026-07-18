<?php

use App\Actions\SendPendingWalletDepositApprovalEmailsAction;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Mail\NewWalletDepositPendingApprovalMail;
use App\Models\Admin;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends pending wallet deposit approval email to all admins', function () {
    Mail::fake();

    // Create admins
    $admin1 = Admin::query()->create([
        'name' => 'Admin One',
        'email' => 'admin1@test.com',
        'password' => bcrypt('password'),
    ]);

    $admin2 = Admin::query()->create([
        'name' => 'Admin Two',
        'email' => 'admin2@test.com',
        'password' => bcrypt('password'),
    ]);

    // Create user
    $user = User::factory()->create();

    // Create a wallet transaction
    $transaction = WalletTransaction::query()->create([
        'user_id' => $user->id,
        'type' => WalletTransactionType::DEPOSIT,
        'amount' => 5000.00,
        'status' => WalletTransactionStatus::PENDING,
    ]);

    // Run action
    app(SendPendingWalletDepositApprovalEmailsAction::class)->__invoke($transaction);

    // Assert mail was sent to both admins
    Mail::assertSent(NewWalletDepositPendingApprovalMail::class, function ($mail) use ($admin1, $transaction) {
        return $mail->hasTo($admin1->email) && $mail->transaction->id === $transaction->id;
    });

    Mail::assertSent(NewWalletDepositPendingApprovalMail::class, function ($mail) use ($admin2, $transaction) {
        return $mail->hasTo($admin2->email) && $mail->transaction->id === $transaction->id;
    });
});
