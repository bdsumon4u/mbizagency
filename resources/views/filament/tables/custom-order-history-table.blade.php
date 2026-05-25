<div class="w-full">
    <div class="space-y-2 p-1">
        <?php
            use Filament\Facades\Filament;
            use App\Filament\Pages\AdAccounts;
            use App\Filament\Admin\Resources\AdAccounts\AdAccountResource;
        ?>

        @foreach($records as $record)
            @php
                $adAccountUrl = '#';
                try {
                    if (Filament::getCurrentPanel()?->getId() === 'admin') {
                        $adAccountUrl = AdAccountResource::getUrl('index', ['highlight' => $record->ad_account_id]);
                    } else {
                        $adAccountUrl = AdAccounts::getUrl(['highlight' => $record->ad_account_id]);
                    }
                } catch (\Throwable $e) {
                    $adAccountUrl = '#';
                }
            @endphp
            <div class="@container w-full bg-white dark:bg-gray-900 border border-gray-100 dark:border-white/10 rounded-lg shadow-sm hover:border-gray-200 dark:hover:border-gray-700 dark:border-gray-700 transition-colors overflow-hidden px-1">
                <div class="overflow-x-auto no-scrollbar snap-x snap-mandatory w-full">
                    <div class="flex w-max min-w-full lg:w-full">
                        
                        <!-- Front: Icon & Info (100% of container width on mobile) -->
                        <div class="w-[100cqw] lg:w-auto lg:flex-1 lg:min-w-0 snap-start flex items-center justify-between gap-2 p-2 lg:p-3 shrink-0">
                            <!-- Left Columns: Icon, Date, Info -->
                            <div class="flex items-center lg:items-start gap-1 lg:gap-2 flex-1 min-w-0">
                                <!-- 1. Icon -->
                                <div class="flex-shrink-0 w-8 h-8 lg:w-10 lg:h-10 flex items-center justify-center rounded-lg bg-red-50 text-[#ff3b5c] border border-red-100/50">
                                    <svg class="w-4 h-4 lg:w-5 lg:h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12" />
                                        <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4" />
                                    </svg>
                                </div>

                                <!-- 2. Order Date Time -->
                                <div class="flex flex-col justify-center shrink-0 w-[70px] lg:w-[85px]">
                                    <span class="text-[10px] lg:text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider lg:mt-0.5">Ordered at</span>
                                    <span class="text-[12px] lg:text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight lg:mt-1">{{ $record->created_at->format('d/m/y') }}</span>
                                    <span class="text-[10px] lg:text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $record->created_at->format('h:i A') }}</span>
                                </div>
                                
                                <!-- 3. Ad Account Info -->
                                <div class="flex flex-col min-w-0 flex-1">
                                    <h3 class="text-[12px] lg:text-sm font-semibold text-gray-900 dark:text-gray-100 truncate max-w-full">
                                        <a href="{{ $adAccountUrl }}" class="hover:text-[#ff3b5c] hover:underline transition-colors text-left truncate w-full block">
                                            {{ $record->adAccount?->name ?? 'Deleted Account' }}
                                        </a>
                                    </h3>
                                    <div class="flex flex-col gap-0.5">
                                        <div class="flex items-center gap-1 text-[10px] lg:text-xs text-gray-500 dark:text-gray-400">
                                            <span class="truncate">ID: <a href="https://adsmanager.facebook.com/adsmanager/manage/campaigns?act={{ $record->adAccount?->act_id ?? '' }}" target="_blank" class="hover:text-[#ff3b5c] hover:underline transition-colors">{{ $record->adAccount?->act_id ?? 'N/A' }}</a></span>
                                            <button x-data="{ copy() { navigator.clipboard.writeText('{{ $record->adAccount?->act_id ?? '' }}'); $tooltip('Copied!'); } }" x-on:click="copy()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 transition-colors flex-shrink-0 focus:outline-none" title="Copy ID">
                                                <x-heroicon-o-document-duplicate class="w-2.5 h-2.5 lg:w-3.5 lg:h-3.5" />
                                            </button>
                                        </div>
                                        
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <span class="text-[10px] lg:text-xs text-gray-400 dark:text-gray-500 font-medium tracking-tight">#<a href="{{ $this->getInvoiceUrl($record) }}" target="_blank" class="hover:text-[#ff3b5c] hover:underline transition-colors">{{ $record->id }}</a></span>
                                            @php
                                                $statusLabel = $record->status->getLabel();
                                                $statusColor = $record->status->getColor();
                                                $statusClasses = match($statusColor) {
                                                    'success' => 'bg-green-50 text-green-600 border-green-200',
                                                    'warning' => 'bg-orange-50 text-orange-600 border-orange-200',
                                                    'danger' => 'bg-red-50 text-red-600 border-red-200',
                                                    default => 'bg-gray-50 dark:bg-gray-800/50 text-gray-600 border-gray-200 dark:border-gray-700',
                                                };
                                                $statusIcon = match($statusColor) {
                                                    'success' => 'heroicon-o-check-circle',
                                                    'warning' => 'heroicon-o-clock',
                                                    'danger' => 'heroicon-o-information-circle',
                                                    default => 'heroicon-o-question-mark-circle',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-sm text-[10px] lg:text-[11px] font-bold uppercase tracking-tight {{ $statusClasses }} border">
                                                @svg($statusIcon, 'w-2.5 h-2.5 lg:w-3 lg:h-3')
                                                {{ $statusLabel }}
                                            </span>
                                            @if(! $this->getTable()->getColumn('source')->isToggledHidden())
                                                @php
                                                    $source = $record->source;
                                                    $sourceColor = $source?->getColor() ?? 'gray';
                                                    $sourceIcon = $source?->getIcon() ?? 'heroicon-o-question-mark-circle';
                                                    $sourceClasses = match ($sourceColor) {
                                                        'success' => 'bg-green-50 text-green-600 border-green-200',
                                                        default => 'bg-gray-50 dark:bg-gray-800/50 text-gray-600 border-gray-200 dark:border-gray-700',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded-sm text-[10px] lg:text-[11px] font-bold {{ $sourceClasses }} border cursor-pointer"
                                                    wire:click="mountTableAction('userOrders', '{{ $record->getKey() }}')"
                                                >
                                                    @svg($sourceIcon, 'w-2.5 h-2.5 lg:w-3 lg:h-3')
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Amount (Full Column for Mobile, hidden on desktop here as it's in extended) -->
                            <div wire:click="mountTableAction('viewProof', {{ $record->id }})" class="lg:hidden flex flex-col justify-center items-end shrink-0 pl-3 border-l border-gray-100 min-h-[60px] cursor-pointer">
                                <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Amount</span>
                                <span class="text-[14px] font-bold text-gray-900 dark:text-gray-100 leading-tight mt-0.5">${{ number_format($record->usd_amount, 2) }}</span>
                                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Tk. {{ number_format($record->bdt_amount, 2) }}</span>
                            </div>
                        </div>
                        
                        <!-- Extended Content: Additional Data (Visible on Horizontal Scroll on Mobile, Always Visible on Desktop) -->
                        <div class="w-[100cqw] lg:w-auto snap-start flex justify-around items-baseline gap-3 lg:gap-6 px-3 lg:px-4 py-2 lg:py-3 shrink-0 border-l border-gray-100 bg-gray-50 dark:bg-gray-800/50/50 lg:border-none lg:bg-transparent">
                            
                            <!-- 0. Amount -->
                            <div wire:click="mountTableAction('viewProof', {{ $record->id }})" class="hidden lg:flex flex-col justify-center w-[70px] cursor-pointer">
                                <span class="text-[10px] lg:text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Amount</span>
                                <span class="text-[14px] font-bold text-gray-900 dark:text-gray-100 leading-tight mt-0.5">${{ number_format($record->usd_amount, 2) }}</span>
                                <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">Tk. {{ number_format($record->bdt_amount, 2) }}</span>
                            </div>

                            <!-- 1. Dollar Rate -->
                            <div class="flex flex-col justify-center w-[75px]">
                                <span class="text-[10px] lg:text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">$ Rate</span>
                                <span class="text-[12px] lg:text-sm font-bold text-gray-900 dark:text-gray-100 mt-1 lg:mt-1.5">Tk. {{ number_format($record->dollar_rate, 2) }}</span>
                            </div>

                            @php
                                $newLimit = (float) ($record->new_limit ?? 0);
                                $oldLimit = (float) ($record->old_limit ?? 0);
                                $balance = (float) ($record->balance ?? 0);
                                
                                $oldLimitPercent = $newLimit > 0 ? min(100, ($oldLimit / $newLimit) * 100) : ($oldLimit > 0 ? 100 : 0);
                                $balancePercent = $newLimit > 0 ? min(100, ($balance / $newLimit) * 100) : ($balance > 0 ? 100 : 0);
                            @endphp

                            <!-- 2. Old Limit -->
                            <div class="flex flex-col justify-center w-[65px] lg:w-[85px]">
                                <span class="text-[10px] lg:text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Old Limit</span>
                                <span class="text-[12px] lg:text-sm font-bold text-gray-900 dark:text-gray-100 mt-1 lg:mt-1.5">${{ number_format($oldLimit, 2) }}</span>
                                <div class="w-full bg-gray-200 rounded-full h-1 mt-1 lg:mt-1.5">
                                    <div class="bg-gray-400 h-1 rounded-full" style="width: {{ $oldLimitPercent }}%"></div>
                                </div>
                            </div>

                            <!-- 3. New Limit -->
                            <div class="flex flex-col justify-center w-[65px] lg:w-[85px]">
                                <span class="text-[10px] lg:text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">New Limit</span>
                                <span class="text-[12px] lg:text-sm font-bold text-gray-900 dark:text-gray-100 mt-1 lg:mt-1.5">${{ number_format($newLimit, 2) }}</span>
                                <div class="w-full bg-gray-200 rounded-full h-1 mt-1 lg:mt-1.5">
                                    <div class="bg-green-500 h-1 rounded-full" style="width: 100%"></div>
                                </div>
                            </div>

                            <!-- 4. Balance -->
                            <div class="flex flex-col justify-center w-[65px] lg:w-[85px]">
                                <span class="text-[10px] lg:text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Balance</span>
                                <span class="text-[12px] lg:text-sm font-bold text-green-600 mt-1 lg:mt-1.5">${{ number_format($balance, 2) }}</span>
                                <div class="w-full bg-gray-200 rounded-full h-1 mt-1 lg:mt-1.5">
                                    <div class="bg-blue-500 h-1 rounded-full" style="width: {{ $balancePercent }}%"></div>
                                </div>
                            </div>

                            @if (! $this->getTable()->getColumn('approved_at')->isToggledHidden())
                                <!-- Approved at -->
                                <div class="flex flex-col justify-center w-[75px] lg:w-[95px]">
                                    <span class="text-[10px] lg:text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Approved</span>
                                    <span class="text-[12px] lg:text-sm font-semibold text-gray-900 dark:text-gray-100 mt-1 lg:mt-1.5">{{ $record->approved_at?->format('d/m/y') ?? '---' }}</span>
                                    <span class="text-[10px] lg:text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $record->approved_at?->format('h:i A') ?? '' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</div>
