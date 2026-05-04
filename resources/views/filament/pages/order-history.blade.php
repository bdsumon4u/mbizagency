<x-filament-panels::page class="!pt-0">
    <div class="max-w-7xl mx-auto space-y-3 lg:space-y-6 w-full">
        @unless($this->adAccountId)
        <!-- Header -->
        <div class="pt-2 sm:pt-0">
            <h1 class="text-xs lg:text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Order History</h1>
        </div>

        <!-- Stats Widgets -->
        <div class="flex overflow-x-auto gap-2 lg:gap-6 sm:grid sm:grid-cols-4 no-scrollbar pb-1">
            @foreach($this->stats as $stat)
                <div class="flex items-center gap-2 lg:gap-4 p-2 lg:p-5 bg-white border border-gray-100 rounded-lg shadow-sm min-w-[100px] flex-1">
                    <div class="flex-shrink-0 w-6 h-6 lg:w-12 lg:h-12 flex items-center justify-center rounded-md {{ $stat['icon_bg'] }} {{ $stat['icon_color'] }}">
                        @svg($stat['icon'], 'w-3 h-3 lg:w-6 lg:h-6')
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[8px] lg:text-xs font-medium text-gray-500 tracking-wider truncate">{{ $stat['label'] }}</span>
                        <span class="text-xs lg:text-2xl font-bold text-gray-900 leading-tight lg:mt-1 lg:mb-0.5">{{ $stat['value'] }}</span>
                        <span class="text-[8px] lg:text-xs text-gray-400">{{ $stat['subtext'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        @endunless

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
