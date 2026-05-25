<?php

namespace App\Actions;

use App\Mail\NewWalletDepositPendingApprovalMail;
use App\Models\Admin;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

final class SendPendingWalletDepositApprovalEmailsAction
{
    public function __invoke(WalletTransaction $transaction): void
    {
        $admins = Admin::query()
            ->whereNotNull('email')
            ->get(['id', 'email']);

        foreach ($admins as $admin) {
            $approveUrl = URL::temporarySignedRoute(
                'filament.admin.wallet-transactions.approve',
                now()->addDays(2),
                [
                    'transaction' => $transaction->id,
                    'admin' => $admin->id,
                ],
            );

            $rejectUrl = URL::temporarySignedRoute(
                'filament.admin.wallet-transactions.reject',
                now()->addDays(2),
                [
                    'transaction' => $transaction->id,
                    'admin' => $admin->id,
                ],
            );

            Mail::to($admin->email)->send(new NewWalletDepositPendingApprovalMail($transaction, $approveUrl, $rejectUrl));
        }
    }
}
