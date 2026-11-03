<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Jobs\AnalyzePaymentScreenshotJob;
use App\Models\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        if ($order->status === OrderStatus::PENDING && ! empty($order->screenshots)) {
            AnalyzePaymentScreenshotJob::dispatch(Order::class, $order->id);
        }
    }
}
