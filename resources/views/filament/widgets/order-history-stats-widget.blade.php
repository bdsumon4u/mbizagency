<x-filament-widgets::widget>
    <div x-data="{ showOtherStats: $wire.entangle('showOtherStats') }" class="space-y-3">
        @if($isToggleable)
            <div class="flex items-center justify-between">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Deposit Stats
                </div>
                <button
                    type="button"
                    @click="showOtherStats = !showOtherStats"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors shadow-sm cursor-pointer"
                >
                    <x-heroicon-o-eye class="w-4 h-4 text-gray-500" x-show="!showOtherStats" />
                    <x-heroicon-o-eye-slash class="w-4 h-4 text-gray-500" x-show="showOtherStats" x-cloak />
                    <span x-text="showOtherStats ? 'Hide Stats' : 'Show Stats'">Show Stats</span>
                </button>
            </div>
        @endif

        <div class="flex overflow-x-auto gap-2 lg:gap-6 sm:grid sm:grid-cols-6 no-scrollbar pb-1">
            @foreach($this->getStats() as $stat)
                <div
                    @if($isToggleable && ($stat['key'] ?? '') !== 'pending')
                        x-show="showOtherStats"
                        x-cloak
                    @endif
                    class="flex items-center gap-2 lg:gap-4 p-2 lg:p-5 bg-white dark:bg-gray-900 border border-gray-100 dark:border-white/10 rounded-lg shadow-sm min-w-[160px] flex-1 {{ ($stat['colspan'] ?? 2) == 3 ? 'sm:col-span-3' : 'sm:col-span-2' }}"
                >
                    <div class="flex-shrink-0 w-6 h-6 lg:w-12 lg:h-12 flex items-center justify-center rounded-md {{ $stat['icon_bg'] }} dark:bg-opacity-10 {{ $stat['icon_color'] }}">
                        @svg($stat['icon'], 'w-3 h-3 lg:w-6 lg:h-6')
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[10px] lg:text-xs font-medium text-gray-500 tracking-wider truncate">{{ $stat['label'] }}</span>
                        <span class="text-xs lg:text-2xl font-bold text-gray-900 dark:text-white leading-tight lg:mt-1">{{ $stat['value'] }}</span>
                        @if(isset($stat['bdt_value']))
                            <span class="text-[10px] lg:text-sm font-medium text-gray-500 dark:text-gray-400 leading-tight lg:mb-1">{{ $stat['bdt_value'] }}</span>
                        @endif
                        <span class="text-[10px] lg:text-xs text-gray-400 dark:text-gray-500">{{ $stat['subtext'] }}</span>
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
</x-filament-widgets::widget>
