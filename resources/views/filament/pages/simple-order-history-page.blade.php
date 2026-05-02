<x-filament-panels::page class="!pt-0">
    <div class="max-w-7xl mx-auto space-y-2 w-full">
        <!-- Header -->
        <div class="pt-2 sm:pt-0">
            <h1 class="text-xs font-bold tracking-tight text-gray-950 dark:text-white">Order History</h1>
        </div>

        <!-- Search and Filter -->
        <div class="flex items-center gap-2 w-full">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none text-gray-400">
                    <x-heroicon-o-magnifying-glass class="w-3 h-3" />
                </div>
                <input type="text" wire:model.live="search" class="block w-full pl-6 pr-2 py-1 border border-gray-200 rounded-md leading-4 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 text-[10px] shadow-sm transition duration-150 ease-in-out" placeholder="Search ad account...">
            </div>
            <button class="flex items-center gap-1 px-2 py-1 bg-white border border-gray-200 rounded-md text-[10px] font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-primary-500 shadow-sm transition-colors">
                <x-heroicon-o-funnel class="w-3 h-3 text-gray-500" />
                Filter
            </button>
        </div>

        <!-- Stats Widgets -->
        <div class="flex overflow-x-auto gap-2 sm:grid sm:grid-cols-3 no-scrollbar pb-1">
            @foreach($this->stats as $stat)
                <div class="flex items-center gap-2 p-2 bg-white border border-gray-100 rounded-lg shadow-sm min-w-[100px] flex-1">
                    <div class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-md {{ $stat['icon_bg'] }} {{ $stat['icon_color'] }}">
                        @svg($stat['icon'], 'w-3 h-3')
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[8px] font-medium text-gray-500 tracking-wider truncate">{{ $stat['label'] }}</span>
                        <span class="text-xs font-bold text-gray-900 leading-tight">{{ $stat['value'] }}</span>
                        <span class="text-[8px] text-gray-400">{{ $stat['subtext'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Vertical Card List -->
        <div class="space-y-2 pb-4">
            @foreach($this->adAccounts as $account)
                <div class="flex items-start justify-between gap-2 p-2 bg-white border border-gray-100 rounded-lg shadow-sm hover:border-gray-200 transition-colors">
                    
                    <!-- Left: Icon & Info -->
                    <div class="flex items-start gap-2 flex-1 min-w-0">
                        <div class="flex-shrink-0 w-6 h-6 flex items-center justify-center rounded-md bg-red-50 text-[#ff3b5c] border border-red-100/50">
                            <x-heroicon-o-building-storefront class="w-3 h-3" />
                        </div>
                        
                        <div class="flex flex-col min-w-0 flex-1">
                            <h3 class="text-[10px] font-semibold text-gray-900 truncate pr-1">{{ $account['name'] }}</h3>
                            
                            <div class="flex items-center gap-1 mt-0.5 text-[9px] text-gray-500">
                                <span class="truncate">ID: {{ $account['id'] }}</span>
                                <button x-data="{ copy() { navigator.clipboard.writeText('{{ $account['id'] }}'); $tooltip('Copied!'); } }" x-on:click="copy()" class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0 focus:outline-none" title="Copy ID">
                                    <x-heroicon-o-document-duplicate class="w-2.5 h-2.5" />
                                </button>
                            </div>
                            
                            <div class="mt-1">
                                @if($account['status'] === 'Approved')
                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[8px] font-medium bg-green-50 text-green-600 border border-green-200">
                                        <x-heroicon-o-check-circle class="w-2 h-2" />
                                        {{ $account['status'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right: Balance & Buttons -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <!-- Balance -->
                        <div class="flex flex-col items-end justify-center">
                            <span class="text-[8px] text-gray-500 tracking-wider font-medium">Balance</span>
                            <span class="text-[10px] font-bold text-green-600">{{ $account['balance'] }}</span>
                        </div>

                        <!-- Buttons -->
                        <div class="flex flex-col gap-1 w-[55px]">
                            <button wire:click="topUp('{{ $account['id'] }}')" class="inline-flex items-center justify-center gap-0.5 w-full px-0 py-0.5 text-[8px] font-semibold text-white bg-[#ff3b5c] hover:bg-[#e63553] rounded transition-colors shadow-sm focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-[#ff3b5c]">
                                + TopUp
                            </button>
                            <button wire:click="openAccount('{{ $account['id'] }}')" class="inline-flex items-center justify-center gap-0.5 w-full px-0 py-0.5 text-[8px] font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded transition-colors focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-gray-200">
                                <x-heroicon-o-arrow-top-right-on-square class="w-2 h-2 text-gray-500" />
                                Open
                            </button>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
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
</x-filament-panels::page>
