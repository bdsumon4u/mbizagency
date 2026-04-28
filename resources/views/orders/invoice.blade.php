<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->id }}</title>
    <style>
        :root {
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-500: #64748b;
            --slate-700: #334155;
            --slate-900: #0f172a;
            --emerald-50: #ecfdf5;
            --emerald-700: #047857;
            --amber-50: #fffbeb;
            --amber-700: #b45309;
            --rose-50: #fff1f2;
            --rose-700: #be123c;
            --indigo-600: #4f46e5;
            --indigo-700: #4338ca;
        }

        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            color: var(--slate-900);
            margin: 0;
            background: var(--slate-100);
        }

        .invoice {
            max-width: 820px;
            margin: 16px auto;
            background: #fff;
            border: 1px solid var(--slate-200);
            border-radius: 16px;
            box-shadow: 0 20px 35px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding: 22px 24px;
            border-bottom: 1px solid var(--slate-200);
            background: linear-gradient(180deg, #fff 0%, var(--slate-50) 100%);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--indigo-600);
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.02em;
        }

        .title {
            margin: 0;
            font-size: 22px;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .subtitle {
            margin: 4px 0 0;
            color: var(--slate-500);
            font-size: 12px;
        }

        .meta {
            text-align: right;
            font-size: 12px;
            color: var(--slate-700);
            line-height: 1.6;
        }

        .badge {
            display: inline-block;
            border-radius: 9999px;
            padding: 3px 9px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border: 1px solid transparent;
        }

        .badge-pending {
            background: var(--amber-50);
            color: var(--amber-700);
            border-color: #fde68a;
        }

        .badge-approved {
            background: var(--emerald-50);
            color: var(--emerald-700);
            border-color: #a7f3d0;
        }

        .badge-rejected {
            background: var(--rose-50);
            color: var(--rose-700);
            border-color: #fecdd3;
        }

        .grid {
            padding: 18px 24px 0;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .card {
            border: 1px solid var(--slate-200);
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
        }

        .card-header {
            padding: 10px 12px;
            border-bottom: 1px solid var(--slate-200);
            background: var(--slate-50);
            font-size: 12px;
            font-weight: 600;
            color: var(--slate-700);
        }

        .rows {
            padding: 4px 0;
        }

        .row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 8px;
            padding: 7px 12px;
            border-top: 1px solid var(--slate-100);
            font-size: 13px;
        }

        .row:first-child {
            border-top: 0;
        }

        .row .key {
            color: var(--slate-500);
            display: flex;
            align-items: center;
        }

        .row .value {
            color: var(--slate-900);
            font-weight: 500;
        }

        .full {
            grid-column: 1 / -1;
        }

        .note {
            padding: 12px;
            font-size: 13px;
            color: var(--slate-700);
            line-height: 1.45;
            white-space: pre-line;
        }

        .proof-wrap {
            padding: 10px 12px 12px;
        }

        .proof-image {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            background: var(--slate-50);
        }

        .proof-empty {
            border: 1px dashed var(--slate-200);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            color: var(--slate-500);
            font-size: 13px;
            background: var(--slate-50);
        }

        .footer {
            border-top: 1px solid var(--slate-200);
            margin-top: 16px;
            padding: 12px 24px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            color: var(--slate-500);
            font-size: 12px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            border: 0;
            border-radius: 8px;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--indigo-600);
            color: #ffffff;
        }

        .btn-primary:hover {
            background: var(--indigo-700);
        }

        .btn-secondary {
            background: var(--slate-200);
            color: var(--slate-700);
        }

        @media (max-width: 720px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                align-items: stretch;
            }

            .meta {
                text-align: left;
            }

            .footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .row {
                grid-template-columns: 1fr;
                gap: 2px;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .invoice {
                margin: 0;
                max-width: none;
                box-shadow: none;
                border-radius: 0;
                border: 0;
            }

            .actions {
                display: none;
            }

            .footer {
                padding-bottom: 0;
            }
        }
    </style>
</head>
<body>
    @php
        use Illuminate\Support\Facades\Storage;
        $statusClass = match ($order->status->value) {
            'approved' => 'badge badge-approved',
            'rejected' => 'badge badge-rejected',
            default => 'badge badge-pending',
        };
    @endphp

    <div class="invoice">
        <div class="header">
            <div class="brand">
                <div class="logo">-</div>
                <div>
                    <h1 class="title">Invoice</h1>
                    <p class="subtitle">{{ config('app.name') }}</p>
                </div>
            </div>
            <div class="meta">
                <div><strong>Invoice #</strong> {{ $order->id }}</div>
                <div><strong>Date</strong> {{ $order->created_at?->format('d M Y h:i A') }}</div>
                <div><strong>Status</strong> <span class="{{ $statusClass }}">{{ $order->status->value }}</span></div>
            </div>
        </div>

        <div class="grid">
            <section class="card">
                <div class="card-header">Customer & Account</div>
                <div class="rows">
                    <div class="row">
                        <div class="key">User</div>
                        <div class="value">
                            <div class="value-name">{{ $order->user?->name ?: 'N/A' }}</div>
                            <div class="value-email">{{ $order->user?->email ?: 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="key">Ad Account</div>
                        <div class="value">
                            <div class="value-name">{{ $order->adAccount?->name ?: 'N/A' }}</div>
                            <div class="value-act-id">{{ $order->adAccount?->act_id ?: 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="key">Approved At</div>
                        <div class="value">{{ $order->approved_at?->format('d M Y h:i A') ?: 'Pending Approval' }}</div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">Pricing Details</div>
                <div class="rows">
                    <div class="row">
                        <div class="key">USD Amount</div>
                        <div class="value">{{ number_format((float) $order->usd_amount, 2) }} USD</div>
                    </div>
                    <div class="row">
                        <div class="key">Dollar Rate</div>
                        <div class="value">{{ number_format((float) $order->dollar_rate, 4) }} BDT</div>
                    </div>
                    <div class="row">
                        <div class="key">Total (BDT)</div>
                        <div class="value">{{ number_format((float) $order->bdt_amount, 2) }} BDT</div>
                    </div>
                    <div class="row">
                        <div class="key">Spend Cap</div>
                        <div class="value">{{ $order->new_limit !== null ? number_format((float) $order->new_limit, 0) : 'N/A' }}</div>
                    </div>
                </div>
            </section>

            <section class="card full">
                <div class="card-header">Payment Proof</div>
                <div class="proof-wrap">
                    @if ($order->screenshots)
                        @foreach ($order->screenshots as $screenshot)
                        <img
                            src="{{ Storage::disk('public')->url($screenshot) }}"
                                alt="Order Screenshot"
                                class="proof-image"
                            >
                        @endforeach
                    @else
                        <div class="proof-empty">No screenshots uploaded.</div>
                    @endif
                </div>
            </section>

            <section class="card full">
                <div class="card-header">Note</div>
                <div class="note">{{ $order->note ?: 'No note provided.' }}</div>
            </section>
        </div>

        <div class="footer">
            <div>Generated from MbizCRM • {{ now()->format('d M Y h:i A') }}</div>
            <div>Approved By: <span class="value-name">{{ $order->admin?->name ?: 'Pending Approval' }}</span></div>
            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="window.close()">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
            </div>
        </div>
    </div>
</body>
</html>
