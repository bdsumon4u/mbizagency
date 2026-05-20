<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Wallet Deposit Pending Approval</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #111827;">
    <h2 style="margin-bottom: 12px;">New wallet deposit pending approval</h2>

    <p>A new wallet deposit has been submitted and requires approval.</p>

    @php
        use Illuminate\Support\Facades\Storage;
    @endphp

    <table cellpadding="6" cellspacing="0" border="0" style="border-collapse: collapse; margin: 12px 0;">
        <tr>
            <td><strong>User</strong></td>
            <td>{{ $transaction->user?->name }} ({{ $transaction->user?->email }})</td>
        </tr>
        <tr>
            <td><strong>Payment Method</strong></td>
            <td>{{ $transaction->paymentMethod?->name }}</td>
        </tr>
        <tr>
            <td><strong>Deposit Amount (BDT)</strong></td>
            <td>{{ number_format((float) $transaction->amount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Note</strong></td>
            <td>{{ $transaction->note ?: 'No note provided.' }}</td>
        </tr>
    </table>

    <p style="margin: 20px 0;">
        <a href="{{ $approveUrl }}" style="background: #2563eb; color: #ffffff; padding: 10px 14px; text-decoration: none; border-radius: 6px;">
            Approve Deposit
        </a>
        <a href="{{ $rejectUrl }}" style="background: #dc2626; color: #ffffff; padding: 10px 14px; text-decoration: none; border-radius: 6px; margin-left: 8px;">
            Reject Deposit
        </a>
    </p>

    <p style="margin: 0 0 10px 0;">Payment screenshots:</p>

    @if ($transaction->screenshots)
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            @foreach ($transaction->screenshots as $screenshot)
                @php
                    if (!is_string($screenshot)) {
                        continue;
                    }
                    $disk = Storage::disk('public');
                    $screenshotPath = $disk->exists($screenshot) ? $disk->path($screenshot) : null;
                    $screenshotSrc = $screenshotPath && isset($message)
                        ? $message->embed($screenshotPath)
                        : $disk->url($screenshot);
                @endphp
                <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px;">
                    <img
                        src="{{ $screenshotSrc }}"
                        alt="Deposit Screenshot"
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
