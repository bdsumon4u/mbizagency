<?php

namespace App\Http\Controllers;

use App\Actions\ApproveOrderAction;
use App\Filament\Pages\OrderHistory;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class ApproveOrderController extends Controller
{
    public function __invoke(Request $request, Order $order): RedirectResponse
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

        try {
            app(ApproveOrderAction::class)($order, $authenticatedAdmin);
        } catch (RuntimeException $exception) {
            return redirect()
                ->to(OrderHistory::getUrl(panel: 'admin'))
                ->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            return redirect()
                ->to(OrderHistory::getUrl(panel: 'admin'))
                ->with('error', 'Order approval failed: '.$exception->getMessage());
        }

        return redirect()
            ->to(OrderHistory::getUrl(panel: 'admin'))
            ->with('success', 'Order approved successfully.');
    }
}
