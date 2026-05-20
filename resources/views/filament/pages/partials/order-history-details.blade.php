@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="space-y-4">
    <!-- Payment Details Card -->
    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 rounded-xl p-4 space-y-3">
        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Payment Details</h3>
        <div class="grid grid-cols-2 gap-y-2.5 text-sm">
            <span class="text-gray-500 dark:text-gray-400 font-medium">Payment Source</span>
            <span class="text-gray-900 dark:text-gray-100 font-semibold text-right">
                @if(($record->payment_source ?? 'payment_method') === 'wallet')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/30">
                        @svg('heroicon-s-wallet', 'w-3.5 h-3.5')
                        Wallet
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/30">
                        @svg('heroicon-s-credit-card', 'w-3.5 h-3.5')
                        Direct Payment
                    </span>
                @endif
            </span>

            <span class="text-gray-500 dark:text-gray-400 font-medium">USD Amount</span>
            <span class="text-gray-900 dark:text-gray-100 font-bold text-right">${{ number_format($record->usd_amount, 2) }}</span>

            <span class="text-gray-500 dark:text-gray-400 font-medium">Conversion Rate</span>
            <span class="text-gray-900 dark:text-gray-100 font-semibold text-right">Tk. {{ number_format($record->dollar_rate, 2) }}</span>

            <div class="col-span-2 border-t border-dashed border-gray-200 dark:border-gray-700 my-1"></div>

            <span class="text-gray-500 dark:text-gray-400 font-medium">BDT Amount</span>
            <span class="text-gray-900 dark:text-gray-100 font-semibold text-right">Tk. {{ number_format($record->bdt_amount, 2) }}</span>

            <span class="text-gray-500 dark:text-gray-400 font-medium">Processing Fee / Charge</span>
            <span class="text-gray-900 dark:text-gray-100 font-semibold text-right">Tk. {{ number_format($record->processing_fee ?? 0, 2) }}</span>

            <div class="col-span-2 border-t border-gray-200 dark:border-gray-700 my-1"></div>

            <span class="text-gray-900 dark:text-gray-100 font-bold">Total Payable</span>
            <span class="text-primary-600 dark:text-primary-400 font-extrabold text-right text-base">Tk. {{ number_format($record->bdt_amount + ($record->processing_fee ?? 0), 2) }}</span>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Note</h3>
        <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
            {{ $record->note ?: 'No note provided.' }}
        </p>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Screenshot</h3>
        @if ($record->screenshots)
            @foreach ($record->screenshots as $screenshot)
            <img
                src="{{ Storage::disk('public')->url($screenshot) }}"
                alt="Order screenshot"
                class="mt-2 max-h-96 w-full rounded-lg border border-gray-200 object-contain dark:border-gray-700"
            />
            @endforeach
        @else
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No screenshots uploaded.</p>
        @endif
    </div>
</div>
