<div class="space-y-2 p-1">
    @foreach($records as $record)
        <div class="@container w-full bg-white border border-gray-100 rounded-lg shadow-sm hover:border-gray-200 transition-colors overflow-hidden px-1">
            <div class="overflow-x-auto no-scrollbar snap-x snap-mandatory w-full">
                <div class="flex w-max min-w-full lg:w-full">
                    
                    <!-- Front: Icon & Info (100% of container width on mobile) -->
                    <div class="w-[100cqw] lg:w-auto lg:flex-1 lg:min-w-0 snap-start flex items-start justify-between gap-2 p-2 lg:p-3 shrink-0">
                        <!-- Left: Icon & Info -->
                        <div class="flex items-start gap-2 flex-1 min-w-0">
                            <div class="flex-shrink-0 w-6 h-6 lg:w-8 lg:h-8 flex items-center justify-center rounded-md bg-red-50 text-[#ff3b5c] border border-red-100/50">
                                <svg class="w-3 h-3 lg:w-4 lg:h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 8v-3a1 1 0 0 0 -1 -1h-10a2 2 0 0 0 0 4h12a1 1 0 0 1 1 1v3m0 4v3a1 1 0 0 1 -1 1h-12a2 2 0 0 1 -2 -2v-12" />
                                    <path d="M20 12v4h-4a2 2 0 0 1 0 -4h4" />
                                </svg>
                            </div>
                            
                            <div class="flex flex-col min-w-0 flex-1">
                                <h3 class="text-[12px] lg:text-sm font-semibold text-gray-900 truncate pr-1">
                                    <button type="button" @unless($this->adAccountId)wire:click="mountTableAction('orders', {{ $record->id }})" class="hover:text-[#ff3b5c] hover:underline transition-colors text-left"@endunless>
                                        {{ $record->adAccount?->name ?? 'Deleted Account' }}
                                    </button>
                                </h3>                                
                                <div class="flex justify-between gap-1">
                                    <div>
                                        <div class="flex items-center gap-0.5 mt-0.5 text-[10px] lg:text-xs text-gray-500">
                                            ID: <a href="https://adsmanager.facebook.com/adsmanager/manage/campaigns?act=act_{{ $record->adAccount?->act_id }}" target="_blank" class="truncate hover:underline hover:text-[#ff3b5c] transition-colors">
                                                {{ $record->adAccount?->act_id ?? 'N/A' }}
                                            </a>
                                            <button x-data="{ copy() { navigator.clipboard.writeText('{{ $record->adAccount?->act_id ?? '' }}'); $tooltip('Copied!'); } }" x-on:click="copy()" class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0 focus:outline-none" title="Copy ID">
                                                <x-heroicon-o-document-duplicate class="w-2.5 h-2.5 lg:w-3.5 lg:h-3.5" />
                                            </button>
                                        </div>
                                        
                                        <div class="mt-1 flex items-center gap-1">
                                            <span class="text-[10px] lg:text-xs text-gray-500 font-medium">#{{ $record->id }}</span>
                                            @php
                                                $statusLabel = $record->status->getLabel();
                                                $statusColor = $record->status->getColor();
                                                $statusClasses = match($statusColor) {
                                                    'success' => 'bg-green-50 text-green-600 border-green-200',
                                                    'warning' => 'bg-orange-50 text-orange-600 border-orange-200',
                                                    'danger' => 'bg-red-50 text-red-600 border-red-200',
                                                    default => 'bg-gray-50 text-gray-600 border-gray-200',
                                                };
                                                $statusIcon = match($statusColor) {
                                                    'success' => 'heroicon-o-check-circle',
                                                    'warning' => 'heroicon-o-clock',
                                                    'danger' => 'heroicon-o-information-circle',
                                                    default => 'heroicon-o-question-mark-circle',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-0.5 px-1.5 lg:px-2 py-0.5 rounded-sm text-[10px] lg:text-[12px] font-medium {{ $statusClasses }} border">
                                                @svg($statusIcon, 'w-2 h-2 lg:w-3 lg:h-3')
                                                {{ $statusLabel }}
                                            </span>
                                            @if(! $this->getTable()->getColumn('source')->isToggledHidden())
                                                @php
                                                    $source = $record->source;
                                                    $sourceColor = $source?->getColor() ?? 'gray';
                                                    $sourceIcon = $source?->getIcon() ?? 'heroicon-o-question-mark-circle';
                                                    $sourceClasses = match ($sourceColor) {
                                                        'success' => 'bg-green-50 text-green-600 border-green-200',
                                                        default => 'bg-gray-50 text-gray-600 border-gray-200',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center gap-0.5 px-0.5 py-0.5 rounded-sm text-[10px] lg:text-[12px] font-medium {{ $sourceClasses }} border">
                                                    @svg($sourceIcon, 'w-2 h-2 lg:w-3 lg:h-3')
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <!-- Right: Amount & Buttons (MOBILE ONLY) -->
                                    <div class="flex lg:hidden items-center gap-2 flex-shrink-0">
                                        <!-- Amount -->
                                        <div wire:click="mountTableAction('viewProof', {{ $record->id }})" class="flex flex-col justify-center lg:w-[85px]">
                                            <span class="text-[10px] lg:text-xs text-gray-500 font-medium mt-0.5">Amount</span>
                                            <span class="text-[12px] lg:text-sm font-semibold text-gray-900 mt-0.5">${{ number_format($record->usd_amount, 2) }}</span>
                                            <span class="text-[10px] lg:text-xs text-gray-400 mt-0.5">Tk. {{ number_format($record->bdt_amount, 2) }}</span>
                                        </div>

                                        @unless($this->adAccountId)
                                        <!-- Buttons -->
                                        <div class="flex flex-col gap-1 w-[55px]">
                                            <button wire:click="mountTableAction('orders', {{ $record->id }})" class="inline-flex items-center justify-center gap-0.5 w-full px-0 py-0.5 text-[10px] font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded transition-colors focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-gray-200">
                                                <x-heroicon-o-arrow-top-right-on-square class="w-2 h-2 text-gray-500" />
                                                History
                                            </button>
                                            
                                            <!-- Invoice -->
                                            <a href="{{ $this->getInvoiceUrl($record) }}" target="_blank" class="inline-flex items-center justify-center gap-0.5 w-full px-0 py-0.5 text-[10px] font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded transition-colors focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-gray-200">
                                                <x-heroicon-o-arrow-top-right-on-square class="w-2 h-2 text-gray-500" />
                                                Invoice
                                            </a>
                                        </div>
                                        @endunless
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Extended Content: Additional Data (Visible on Horizontal Scroll on Mobile, Always Visible on Desktop) -->
                    <div class="w-[100cqw] lg:w-auto snap-start flex justify-around items-baseline gap-3 px-3 lg:px-4 py-2 lg:py-3 shrink-0 border-l border-gray-100 bg-gray-50/50 lg:border-none lg:bg-transparent">
                        <!-- Date & Time -->
                        <div class="flex flex-col justify-center lg:w-[85px]">
                            <span class="text-[10px] lg:text-xs text-gray-500 font-medium">Ordered at</span>
                            <span class="text-[12px] lg:text-sm font-semibold text-gray-900 mt-0.5 lg:mt-1">{{ $record->created_at->format('d/m/y') }}</span>
                            <span class="text-[10px] lg:text-xs text-gray-400 mt-0.5">{{ $record->created_at->format('h:i A') }}</span>
                        </div>

                        @if (! $this->getTable()->getColumn('approved_at')->isToggledHidden())
                            <!-- Approved at -->
                            <div class="flex flex-col justify-center lg:w-[85px]">
                                <span class="text-[10px] lg:text-xs text-gray-500 font-medium">Approved at</span>
                                <span class="text-[12px] lg:text-sm font-semibold text-gray-900 mt-0.5 lg:mt-1">{{ $record->approved_at?->format('d/m/y') ?? '---' }}</span>
                                <span class="text-[10px] lg:text-xs text-gray-400 mt-0.5">{{ $record->approved_at?->format('h:i A') ?? '---' }}</span>
                            </div>
                        @endif
                        
                        <!-- Amount -->
                        <div wire:click="mountTableAction('viewProof', {{ $record->id }})" class="flex-col cursor-pointer justify-center lg:w-[85px] hidden lg:flex">
                            <span class="text-[10px] lg:text-xs text-gray-500 font-medium">Amount</span>
                            <span class="text-[12px] lg:text-sm font-semibold text-gray-900 mt-0.5 lg:mt-1">${{ number_format($record->usd_amount, 2) }}</span>
                            <span class="text-[10px] lg:text-xs text-gray-400 mt-0.5">Tk. {{ number_format($record->bdt_amount, 2) }}</span>
                        </div>

                        <!-- Balance -->
                        <div class="flex lg:hidden flex-col justify-center lg:w-[85px]">
                            <span class="text-[10px] lg:text-xs text-gray-500 font-medium">Balance</span>
                            <span class="text-[12px] lg:text-sm font-semibold text-gray-900 mt-0.5 lg:mt-1">${{ number_format($record->balance ?? 0, 2) }}</span>
                        </div>
                        
                        <!-- Dollar Rate -->
                        <div class="flex flex-col justify-center lg:w-[75px]">
                            <span class="text-[10px] lg:text-xs text-gray-500 font-medium text-nowrap">Dollar Rate</span>
                            <span class="text-[12px] lg:text-sm font-semibold text-gray-900 mt-0.5 lg:mt-1">Tk. {{ number_format($record->dollar_rate, 2) }}</span>
                        </div>
                        
                        <!-- Limit -->
                        <div class="flex flex-col justify-center lg:w-[70px]">
                            <span class="text-[10px] lg:text-xs text-gray-500 font-medium text-nowrap">Spend Limit</span>
                            <span class="text-[12px] lg:text-sm font-semibold text-gray-900 mt-0.5 lg:mt-1">${{ number_format($record->new_limit ?? 0, 2) }}</span>
                            <span class="text-[10px] lg:text-xs text-gray-400 line-through mt-0.5">${{ number_format($record->old_limit ?? 0, 2) }}</span>
                        </div>

                        <!-- Desktop Balance & Buttons -->
                        <div class="hidden lg:flex items-center gap-3 flex-shrink-0 ml-2 pl-4 border-l border-gray-200 self-center">
                            <!-- Balance -->
                            <div class="flex flex-col items-end justify-center lg:w-[60px]">
                                <span class="text-xs text-gray-500 tracking-wider font-medium">Balance</span>
                                <span class="text-sm font-bold text-green-600 mt-0.5">${{ number_format($record->balance ?? 0, 2) }}</span>
                            </div>

                            @unless($this->adAccountId)
                            <!-- Buttons -->
                            <div class="flex flex-col gap-1.5 w-[75px]">
                                <button wire:click="mountTableAction('orders', {{ $record->id }})" class="inline-flex items-center justify-center gap-1 w-full px-0 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded transition-colors focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-gray-200">
                                    <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3 text-gray-500" />
                                    History
                                </button>
                                <a href="{{ $this->getInvoiceUrl($record) }}" target="_blank" class="inline-flex items-center justify-center gap-1 w-full px-0 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded transition-colors focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-gray-200">
                                    <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3 text-gray-500" />
                                    Invoice
                                </a>
                            </div>
                            @endunless
                        </div>
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
