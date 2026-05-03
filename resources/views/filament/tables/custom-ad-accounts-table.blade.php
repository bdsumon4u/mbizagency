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
                                <x-heroicon-o-wallet class="w-3 h-3 lg:w-4 lg:h-4" />
                            </div>
                            
                            <div class="flex flex-col min-w-0 flex-1">
                                <h3 class="text-[10px] lg:text-sm font-semibold text-gray-900 truncate pr-1">
                                    <button type="button" wire:click="mountTableAction('orders', '{{ $record->id }}')" class="hover:text-[#ff3b5c] hover:underline transition-colors text-left cursor-pointer">
                                        {{ $record->name ?? 'Account ID: ' . $record->id }}
                                    </button>
                                </h3>
                                
                                <div class="flex justify-between gap-1">
                                    <div>
                                        <div class="flex items-center gap-1 mt-0.5 text-[9px] lg:text-xs text-gray-500">
                                            <span class="truncate">ID: {{ $record->act_id ?? $record->id }}</span>
                                            <button x-data="{ copy() { navigator.clipboard.writeText('{{ $record->act_id ?? $record->id }}'); $tooltip('Copied!'); } }" x-on:click="copy()" class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0 focus:outline-none" title="Copy ID">
                                                <x-heroicon-o-document-duplicate class="w-2.5 h-2.5 lg:w-3.5 lg:h-3.5" />
                                            </button>
                                        </div>
                                        
                                        <div class="mt-1 flex items-center gap-2">
                                            @php
                                                $statusColor = match($record->status->getColor()) {
                                                    'success' => 'bg-green-50 text-green-600 border-green-200',
                                                    'danger' => 'bg-red-50 text-red-600 border-red-200',
                                                    'warning' => 'bg-orange-50 text-orange-600 border-orange-200',
                                                    'info' => 'bg-blue-50 text-blue-600 border-blue-200',
                                                    default => 'bg-gray-50 text-gray-600 border-gray-200',
                                                };
                                                $statusIcon = $record->status->getIcon();
                                            @endphp
                                            <span class="inline-flex items-center gap-0.5 px-1.5 lg:px-2 py-0.5 rounded-full text-[8px] lg:text-[10px] font-medium border {{ $statusColor }}">
                                                @svg($statusIcon, 'w-2 h-2 lg:w-3 lg:h-3')
                                                {{ $record->status->getLabel() }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Right: Action Buttons (MOBILE ONLY) -->
                                    <div class="flex lg:hidden items-center gap-2 flex-shrink-0">
                                        <!-- Balance -->
                                        <div class="flex flex-col items-end justify-center">
                                            <span class="text-[8px] text-gray-500 tracking-wider font-medium">Balance</span>
                                            <span class="text-[10px] font-bold text-green-600">${{ number_format((float) ($record->balance ?? 0), 2) }}</span>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="flex flex-col gap-1 w-[55px]">
                                            <button type="button" wire:click="mountTableAction('add_fund', '{{ $record->id }}')" wire:target="mountTableAction('add_fund', '{{ $record->id }}')" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-0.5 w-full px-0 py-0.5 text-[8px] font-semibold text-white bg-[#ff3b5c] hover:bg-[#e63553] rounded transition-colors shadow-sm focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-[#ff3b5c] disabled:opacity-70">
                                                <x-heroicon-o-plus class="w-2.5 h-2.5" wire:loading.remove wire:target="mountTableAction('add_fund', '{{ $record->id }}')" />
                                                <x-heroicon-o-arrow-path class="w-2.5 h-2.5 animate-spin" wire:loading wire:target="mountTableAction('add_fund', '{{ $record->id }}')" />
                                                TopUp
                                            </button>
                                            <button type="button" wire:click="syncSingle({{ $record->id }})" wire:target="syncSingle({{ $record->id }})" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-0.5 w-full px-0 py-0.5 text-[8px] font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded transition-colors focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-gray-200 disabled:opacity-70">
                                                <x-heroicon-o-arrow-path class="w-2 h-2 text-gray-500" wire:loading.remove wire:target="syncSingle({{ $record->id }})" />
                                                <x-heroicon-o-arrow-path class="w-2 h-2 text-gray-500 animate-spin" wire:loading wire:target="syncSingle({{ $record->id }})" />
                                                Sync
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Extended Content: Additional Data (Visible on Horizontal Scroll on Mobile, Always Visible on Desktop) -->
                    <div class="snap-start flex justify-around items-center gap-3 lg:gap-6 px-3 lg:px-4 py-2 lg:py-3 shrink-0 border-l border-gray-100 bg-gray-50/50 lg:border-none lg:bg-transparent w-[100cqw] lg:w-auto">
                        
                        <!-- Limit -->
                        <div class="flex flex-col justify-center w-[60px] lg:w-[80px]">
                            <span class="text-[8px] lg:text-xs text-gray-500 font-medium">Limit</span>
                            <span class="text-[10px] lg:text-sm font-semibold text-gray-900 mt-0.5 lg:mt-1">${{ number_format((float) ($record->spend_cap ?? 0), 2) }}</span>
                            <div class="w-full bg-gray-200 rounded-full h-1 mt-1 lg:mt-1.5">
                                <div class="bg-gray-400 h-1 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                        
                        <!-- Spent -->
                        <div class="flex flex-col justify-center w-[60px] lg:w-[80px]">
                            <span class="text-[8px] lg:text-xs text-gray-500 font-medium">Spent</span>
                            <span class="text-[10px] lg:text-sm font-semibold text-gray-900 mt-0.5 lg:mt-1">${{ number_format((float) ($record->amount_spent ?? 0), 2) }}</span>
                            @php
                                $cap = (float) ($record->spend_cap ?? 0);
                                $spent = (float) ($record->amount_spent ?? 0);
                                $percent = $cap > 0 ? min(100, ($spent / $cap) * 100) : ($spent > 0 ? 100 : 0);
                            @endphp
                            <div class="w-full bg-gray-200 rounded-full h-1 mt-1 lg:mt-1.5">
                                <div class="bg-red-500 h-1 rounded-full" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        <!-- Synced At -->
                        <div class="flex flex-col justify-center w-[60px] lg:w-[80px]">
                            <span class="text-[8px] lg:text-xs text-gray-500 font-medium">Synced At</span>
                            <span class="text-[10px] lg:text-sm font-semibold text-gray-900 mt-0.5 lg:mt-1">{{ $record->synced_at ? $record->synced_at->format('d/m/y') : 'N/A' }}</span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <span class="text-[8px] lg:text-xs text-gray-400">{{ $record->synced_at ? $record->synced_at->format('h:i A') : '' }}</span>
                                @if($record->synced_at)
                                    <span class="w-1.5 h-1.5 lg:w-2 lg:h-2 rounded-full bg-green-500"></span>
                                @endif
                            </div>
                        </div>

                        <!-- Desktop Balance & Buttons -->
                        <div class="hidden lg:flex items-center gap-3 flex-shrink-0 ml-2 pl-4 border-l border-gray-200">
                            <!-- Balance -->
                            <div class="flex flex-col items-end justify-center lg:w-[80px]">
                                <span class="text-xs text-gray-500 tracking-wider font-medium">Balance</span>
                                <span class="text-sm font-bold text-green-600 mt-0.5">${{ number_format((float) ($record->balance ?? 0), 2) }}</span>
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-col gap-1.5 w-[75px]">
                                <button type="button" wire:click="mountTableAction('add_fund', '{{ $record->id }}')" wire:target="mountTableAction('add_fund', '{{ $record->id }}')" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-1 w-full px-0 py-1 text-xs font-semibold text-white bg-[#ff3b5c] hover:bg-[#e63553] rounded transition-colors shadow-sm focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-[#ff3b5c] disabled:opacity-70">
                                    <x-heroicon-o-plus class="w-3 h-3" wire:loading.remove wire:target="mountTableAction('add_fund', '{{ $record->id }}')" />
                                    <x-heroicon-o-arrow-path class="w-3 h-3 animate-spin" wire:loading wire:target="mountTableAction('add_fund', '{{ $record->id }}')" />
                                    TopUp
                                </button>
                                <button type="button" wire:click="syncSingle({{ $record->id }})" wire:target="syncSingle({{ $record->id }})" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-1 w-full px-0 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded transition-colors focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-gray-200 disabled:opacity-70">
                                    <x-heroicon-o-arrow-path class="w-3 h-3 text-gray-500" wire:loading.remove wire:target="syncSingle({{ $record->id }})" />
                                    <x-heroicon-o-arrow-path class="w-3 h-3 text-gray-500 animate-spin" wire:loading wire:target="syncSingle({{ $record->id }})" />
                                    Sync
                                </button>
                            </div>
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
