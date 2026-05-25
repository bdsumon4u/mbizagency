<?php

namespace App\Http\Controllers;

use App\Enums\WalletTransactionStatus;
use App\Models\Admin;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApproveWalletTransactionController extends Controller
{
    public function __invoke(Request $request, WalletTransaction $transaction): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired approval link.');
        }

        $authenticatedAdmin = Auth::guard('admin')->user();
        if (! $authenticatedAdmin instanceof Admin) {
            abort(403, 'Admin authentication is required.');
        }

        $signedAdminId = $request->integer('admin');
        if ($signedAdminId <= 0 || $authenticatedAdmin->id !== $signedAdminId) {
            abort(403, 'Signed admin mismatch.');
        }

        if ($transaction->status !== WalletTransactionStatus::PENDING) {
            return redirect()
                ->route('filament.admin.resources.wallet-transactions.index')
                ->with('error', 'This transaction has already been processed.');
        }

        try {
            DB::transaction(function () use ($transaction, $authenticatedAdmin) {
                $transaction->user->wallet_balance += $transaction->amount;
                $transaction->user->save();

                $transaction->update([
                    'status' => WalletTransactionStatus::APPROVED,
                    'admin_id' => $authenticatedAdmin->id,
                    'approved_at' => now(),
                    'balance_after' => $transaction->user->wallet_balance,
                ]);
            });
        } catch (Throwable $exception) {
            return redirect()
                ->route('filament.admin.resources.wallet-transactions.index')
                ->with('error', 'Wallet transaction approval failed: '.$exception->getMessage());
        }

        return redirect()
            ->route('filament.admin.resources.wallet-transactions.index')
            ->with('success', 'Wallet transaction approved successfully.');
    }
}
