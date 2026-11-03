<?php

namespace App\Http\Controllers;

use App\Actions\ApproveWalletTransactionAction;
use App\Enums\WalletTransactionStatus;
use App\Filament\Pages\Wallet;
use App\Models\Admin;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                ->to(Wallet::getUrl(panel: 'admin'))
                ->with('error', 'This transaction has already been processed.');
        }

        try {
            app(ApproveWalletTransactionAction::class)($transaction, $authenticatedAdmin);
        } catch (Throwable $exception) {
            return redirect()
                ->to(Wallet::getUrl(panel: 'admin'))
                ->with('error', 'Wallet transaction approval failed: '.$exception->getMessage());
        }

        return redirect()
            ->to(Wallet::getUrl(panel: 'admin'))
            ->with('success', 'Wallet transaction approved successfully.');
    }
}
