<div class="space-y-4 text-sm">
    <div class="grid grid-cols-2 gap-2 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
        <div>
            <span class="text-gray-500 dark:text-gray-400">Provider:</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100 uppercase ml-1">{{ $record->provider ?: 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Source:</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100 ml-1">{{ $record->source === 'http_webhook' ? 'HTTP Webhook' : ucfirst($record->source ?? 'Telegram') }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Status:</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100 ml-1">{{ $record->status->getLabel() }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">TrxID:</span>
            <span class="font-mono font-bold text-gray-900 dark:text-gray-100 ml-1">{{ $record->trx_id ?: 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Amount:</span>
            <span class="font-bold text-gray-900 dark:text-gray-100 ml-1">{{ number_format((float) $record->amount, 2) }} BDT</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Receiver A/C:</span>
            <span class="font-mono font-bold text-gray-900 dark:text-gray-100 ml-1">{{ $record->receiver_account ?: 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Sender A/C:</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100 ml-1">{{ $record->sender_account ?: 'N/A (Not in SMS)' }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Balance:</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100 ml-1">{{ $record->balance ? number_format((float) $record->balance, 2) . ' BDT' : 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Fee:</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100 ml-1">{{ number_format((float) $record->fee, 2) }} BDT</span>
        </div>
    </div>

    <div>
        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Raw SMS Message</h4>
        <div class="p-3 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 font-mono text-xs whitespace-pre-wrap text-gray-800 dark:text-gray-200">
            {{ $record->raw_message }}
        </div>
    </div>

    @if ($record->matched_type && $record->matched_id)
        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-xs text-green-800 dark:text-green-300">
            <strong>Matched Record:</strong> {{ class_basename($record->matched_type) }} #{{ $record->matched_id }}
            at {{ $record->matched_at?->format('d M Y h:i A') }}
        </div>
    @endif
</div>

