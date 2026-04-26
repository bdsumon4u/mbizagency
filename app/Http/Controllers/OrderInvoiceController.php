<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderInvoiceController extends Controller
{
    public function __invoke(Request $request, Order $order): View
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired invoice link.');
        }

        $order->loadMissing(['user', 'adAccount', 'admin']);

        return view('orders.invoice', [
            'order' => $order,
        ]);
    }
}
