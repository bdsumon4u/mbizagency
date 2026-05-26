<x-filament-widgets::widget>
    <div class="flex overflow-x-auto gap-2 lg:gap-6 sm:grid sm:grid-cols-4 no-scrollbar pb-1">
        @foreach($this->getStats() as $stat)
            <div class="flex items-center gap-2 lg:gap-4 p-2 lg:p-5 bg-white dark:bg-gray-900 border border-gray-100 dark:border-white/10 rounded-lg shadow-sm min-w-[160px] flex-1">
                <div class="flex-shrink-0 w-6 h-6 lg:w-12 lg:h-12 flex items-center justify-center rounded-md {{ $stat['icon_bg'] }} dark:bg-opacity-10 {{ $stat['icon_color'] }}">
                    @svg($stat['icon'], 'w-3 h-3 lg:w-6 lg:h-6')
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-[10px] lg:text-xs font-medium text-gray-500 tracking-wider truncate">{{ $stat['label'] }}</span>
                    <span class="text-xs lg:text-2xl font-bold text-gray-900 dark:text-white leading-tight lg:mt-1 lg:mb-0.5">{{ $stat['value'] }}</span>
                    <span class="text-[10px] lg:text-xs text-gray-400 dark:text-gray-500">{{ $stat['subtext'] }}</span>
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
</x-filament-widgets::widget>
