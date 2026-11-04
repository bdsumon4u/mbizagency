@php
    /** @var \App\Models\PaymentVerification|null $verification */
    $verification = $record->paymentVerification;
    $sms = $verification?->incomingSms;
@endphp

@if ($verification)
    <div class="mt-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/40 p-4 space-y-3">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    AI Screenshot & SMS Verification
                </span>
            </div>
            <div>
                @php
                    $status = $verification->verification_status;
                    $color = $status->getColor();
                    $label = $status->getLabel();
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                    @if($color === 'success') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                    @elseif($color === 'danger') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                    @elseif($color === 'warning') bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300
                    @else bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300
                    @endif">
                    {{ $label }}
                </span>
            </div>
        </div>

        <!-- Verification Reason / Alert -->
        @if ($verification->status_reason)
            <div class="text-xs p-2.5 rounded-lg
                @if($verification->is_suspicious || $verification->verification_status->value === 'suspicious')
                    bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400 border border-red-200 dark:border-red-800
                @elseif($verification->verification_status->value === 'approved')
                    bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400 border border-green-200 dark:border-green-800
                @else
                    bg-amber-50 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300 border border-amber-200 dark:border-amber-800
                @endif">
                <strong>Status Note:</strong> {{ $verification->status_reason }}
            </div>
        @endif

        <!-- AI Extracted Data Grid -->
        <div class="grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Extracted TrxID:</span>
                <span class="font-mono font-bold text-gray-900 dark:text-gray-100 ml-1">
                    {{ $verification->trx_id ?: 'Not detected' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Reference:</span>
                <span class="font-mono text-gray-900 dark:text-gray-100 ml-1">
                    {{ $verification->reference ?: 'Not detected' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Extracted Amount:</span>
                <span class="font-bold text-gray-900 dark:text-gray-100 ml-1">
                    {{ $verification->amount !== null ? number_format((float) $verification->amount, 2).' BDT' : 'N/A' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Fee:</span>
                <span class="text-gray-900 dark:text-gray-100 ml-1">
                    {{ $verification->fee !== null ? number_format((float) $verification->fee, 2).' BDT' : 'N/A' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Total:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100 ml-1">
                    {{ $verification->total_amount !== null ? number_format((float) $verification->total_amount, 2).' BDT' : 'N/A' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Detected Provider:</span>
                <span class="font-medium capitalize text-gray-900 dark:text-gray-100 ml-1">
                    {{ $verification->provider ?: 'N/A' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">From:</span>
                <span class="font-mono text-gray-900 dark:text-gray-100 ml-1">
                    {{ collect([$verification->sender_name, $verification->sender_account])->filter()->join(' · ') ?: 'Not detected' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">To:</span>
                <span class="font-mono text-gray-900 dark:text-gray-100 ml-1">
                    {{ collect([$verification->receiver_name, $verification->receiver_account])->filter()->join(' · ') ?: 'Not detected' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Transaction Time:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100 ml-1">
                    {{ $verification->transaction_time?->format('d M Y h:i:s A') ?? 'Not detected' }}
                </span>
            </div>
            @if ($verification->txn_type)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Transaction Type:</span>
                    <span class="text-gray-900 dark:text-gray-100 ml-1">{{ $verification->txn_type }}</span>
                </div>
            @endif
            @if ($verification->beneficiary_bank)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Beneficiary Bank:</span>
                    <span class="text-gray-900 dark:text-gray-100 ml-1">{{ $verification->beneficiary_bank }}</span>
                </div>
            @endif
            @if ($verification->balance !== null)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Balance:</span>
                    <span class="text-gray-900 dark:text-gray-100 ml-1">{{ number_format((float) $verification->balance, 2) }} BDT</span>
                </div>
            @endif
            @php($screenshotStatus = data_get($verification->ai_response, 'status_in_screenshot'))
            @if ($screenshotStatus)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Screenshot Status:</span>
                    <span class="capitalize text-gray-900 dark:text-gray-100 ml-1">{{ $screenshotStatus }}</span>
                </div>
            @endif
            <div>
                <span class="text-gray-500 dark:text-gray-400">AI Confidence:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100 ml-1">
                    {{ $verification->confidence_score !== null ? (float) ($verification->confidence_score * 100).'%' : 'N/A' }}
                </span>
            </div>
        </div>

        <!-- Matched Telegram SMS Details -->
        @if ($sms)
            <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700 space-y-1 text-xs">
                <div class="flex items-center justify-between text-gray-600 dark:text-gray-300 font-semibold">
                    <span>Matched Telegram SMS (#{{ $sms->id }})</span>
                    <span class="text-gray-400">{{ $sms->transaction_time?->format('d M Y h:i A') ?? $sms->created_at->format('d M Y h:i A') }}</span>
                </div>
                <div class="bg-white dark:bg-gray-900/60 p-2 rounded border border-gray-200 dark:border-gray-700 font-mono text-[11px] whitespace-pre-wrap text-gray-700 dark:text-gray-300">
                    {{ $sms->raw_message }}
                </div>
            </div>
        @endif
    </div>
@endif

