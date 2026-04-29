<?php

namespace App\Actions;

use App\Mail\NewOrderPendingApprovalMail;
use App\Models\Admin;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

final class SendPendingOrderApprovalEmailsAction
{
    public function __invoke(Order $order): void
    {
        $admins = Admin::query()
            ->whereNotNull('email')
            ->get(['id', 'email']);

        foreach ($admins as $admin) {
            $approveUrl = URL::temporarySignedRoute(
                'filament.admin.orders.approve',
                now()->addDays(2),
                [
                    'order' => $order->id,
                    'admin' => $admin->id,
                ],
            );

            $rejectUrl = URL::temporarySignedRoute(
                'filament.admin.orders.reject',
                now()->addDays(2),
                [
                    'order' => $order->id,
                    'admin' => $admin->id,
                ],
            );

            Mail::to($admin->email)->send(new NewOrderPendingApprovalMail($order, $approveUrl, $rejectUrl));
        }
    }
}
