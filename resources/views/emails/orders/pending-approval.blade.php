<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Order Pending Approval</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #111827;">
    <h2 style="margin-bottom: 12px;">New order pending approval</h2>

    <p>A new order has been submitted and requires approval.</p>

    @php
        use Illuminate\Support\Facades\Storage;

        $processingFeeBdt = (float) ($order->processing_fee ?? 0);
        $totalPayableBdt = (float) $order->bdt_amount + $processingFeeBdt;
    @endphp

    <table cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse; margin: 12px 0;">
        <tr>
            <td><strong>User</strong></td>
            <td>{{ $order->user?->name }} ({{ $order->user?->email }})</td>
        </tr>
        <tr>
            <td><strong>Ad Account</strong></td>
            <td>{{ $order->adAccount?->name }} ({{ $order->adAccount?->act_id }})</td>
        </tr>
        <tr>
            <td><strong>USD Amount</strong></td>
            <td>{{ number_format((float) $order->usd_amount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Dollar Rate</strong></td>
            <td>{{ number_format((float) $order->dollar_rate, 2) }}</td>
        </tr>
        <tr>
            <td><strong>BDT Amount</strong></td>
            <td>{{ number_format((float) $order->bdt_amount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Mobile Banking Charge (BDT)</strong></td>
            <td>{{ number_format($processingFeeBdt, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total Payable (BDT)</strong></td>
            <td>{{ number_format($totalPayableBdt, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Note</strong></td>
            <td>{{ $order->note ?: 'No note provided.' }}</td>
        </tr>
    </table>

    <p style="margin: 20px 0;">
        <a href="{{ $approveUrl }}" style="background: #2563eb; color: #ffffff; padding: 10px 14px; text-decoration: none; border-radius: 6px;">
            Approve Order
        </a>
        <a href="{{ $rejectUrl }}" style="background: #dc2626; color: #ffffff; padding: 10px 14px; text-decoration: none; border-radius: 6px; margin-left: 8px;">
            Reject Order
        </a>
    </p>

    <p style="margin: 0 0 10px 0;">Payment screenshots:</p>

    @if ($order->screenshots)
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            @foreach ($order->screenshots as $screenshot)
                @php
                    $disk = Storage::disk('public');
                    $screenshotPath = $disk->exists($screenshot) ? $disk->path($screenshot) : null;
                    $screenshotSrc = $screenshotPath && isset($message)
                        ? $message->embed($screenshotPath)
                        : $disk->url($screenshot);
                @endphp
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px;">
                    <img
                        src="{{ $screenshotSrc }}"
                        alt="Order Screenshot"
                        style="max-width: 320px; height: auto; display: block; border-radius: 6px;"
                    />
                </div>
            @endforeach
        </div>
    @else
        <p style="margin: 0;">No screenshots uploaded.</p>
    @endif
</body>
</html>
