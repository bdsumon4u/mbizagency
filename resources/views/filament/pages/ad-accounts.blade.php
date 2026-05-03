<x-filament-panels::page class="!pt-0">
    <div class="max-w-7xl mx-auto space-y-3 lg:space-y-6 w-full">
        <!-- Header -->
        <div class="pt-2 sm:pt-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xs lg:text-2xl font-bold tracking-tight text-gray-950 dark:text-white flex items-center gap-2">
                    Ad Account Limits
                    <x-heroicon-o-information-circle class="w-4 h-4 text-gray-400" />
                </h1>
                <p class="text-xs text-gray-500 mt-1">Manage and sync your ad account limits</p>
            </div>
            <button class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-[#ff3b5c] hover:bg-[#e63553] text-white text-sm font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#ff3b5c]">
                <x-heroicon-o-plus-circle class="w-5 h-5" />
                Add Account
            </button>
        </div>

        <!-- Stats Widgets -->
        <div class="flex overflow-x-auto gap-2 lg:gap-6 sm:grid sm:grid-cols-3 no-scrollbar pb-1">
            @foreach($this->stats as $stat)
                <div class="flex items-center gap-3 lg:gap-4 p-3 lg:p-5 bg-white border border-gray-100 rounded-xl shadow-sm min-w-[150px] flex-1">
                    <div class="flex-shrink-0 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center rounded-lg {{ $stat['icon_bg'] }} {{ $stat['icon_color'] }}">
                        @svg($stat['icon'], 'w-5 h-5 lg:w-6 lg:h-6')
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] lg:text-xs font-medium text-gray-500 tracking-wider truncate">{{ $stat['label'] }}</span>
                        <span class="text-sm lg:text-2xl font-bold {{ $stat['icon_color'] === 'text-blue-500' ? 'text-blue-600' : ($stat['icon_color'] === 'text-green-500' ? 'text-green-600' : 'text-red-600') }} leading-tight lg:mt-1 lg:mb-0.5">{{ $stat['value'] }}</span>
                        <span class="text-[9px] lg:text-xs text-gray-400">{{ $stat['subtext'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $this->table }}
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
