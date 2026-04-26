<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Order Pending Approval</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #111827;">
    <h2 style="margin-bottom: 12px;">New order pending approval</h2>

    <p>A new order has been submitted and requires approval.</p>

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
            <td>{{ number_format((float) $order->dollar_rate, 4) }}</td>
        </tr>
        <tr>
            <td><strong>BDT Amount</strong></td>
            <td>{{ number_format((float) $order->bdt_amount, 2) }}</td>
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

    <p>The screenshot is attached with this email.</p>
</body>
</html>
