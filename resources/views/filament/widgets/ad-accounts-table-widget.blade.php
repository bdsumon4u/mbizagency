<x-filament-widgets::widget>
    <div class="space-y-3 lg:space-y-6 w-full">
        <!-- Header -->
        <div class="pt-2 sm:pt-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xs lg:text-2xl font-bold tracking-tight text-gray-950 dark:text-white flex items-center gap-2">
                    Ad Account Limits
                    <x-heroicon-o-information-circle class="w-4 h-4 text-gray-400" />
                </h1>
                <p class="text-xs text-gray-500 mt-1">Manage and sync your ad account limits</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <!-- Wallet Balance -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-green-50/50 dark:bg-green-500/5 border border-green-100 dark:border-green-500/20 rounded-lg">
                    <x-heroicon-o-wallet class="w-4 h-4 text-green-500" />
                    <span class="text-xs font-semibold text-green-700 dark:text-green-400">Tk. {{ number_format(auth()->user()->wallet_balance ?? 0, 2) }}</span>
                </div>

                <!-- Add Funds Button -->
                <div class="flex-shrink-0">
                    {{ $this->depositAction }}
                </div>

                <div class="w-px h-6 bg-gray-200 dark:bg-white/10 mx-1 hidden sm:block"></div>

                <button wire:click="syncAll" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-[#ff3b5c] hover:bg-[#e63553] text-white text-sm font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#ff3b5c] disabled:opacity-70">
                    <x-heroicon-o-arrow-path class="w-5 h-5" wire:loading.class="animate-spin" />
                    Sync All Accounts
                </button>
            </div>
        </div>

        <!-- Stats Widgets -->
        <div class="flex overflow-x-auto gap-2 lg:gap-6 sm:grid sm:grid-cols-3 no-scrollbar pb-1">
            @foreach($this->stats as $stat)
                <div class="flex items-center gap-3 lg:gap-4 p-3 lg:p-5 bg-white dark:bg-gray-900 border border-gray-100 dark:border-white/10 rounded-xl shadow-sm min-w-[180px] flex-1">
                    <div class="flex-shrink-0 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center rounded-lg {{ $stat['icon_bg'] }} {{ $stat['icon_color'] }}">
                        @svg($stat['icon'], 'w-5 h-5 lg:w-6 lg:h-6')
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[12px] lg:text-xs font-medium text-gray-500 dark:text-gray-400 tracking-wider truncate">{{ $stat['label'] }}</span>
                        <span class="text-sm lg:text-2xl font-bold {{ $stat['icon_color'] === 'text-blue-500' ? 'text-blue-600 dark:text-blue-400' : ($stat['icon_color'] === 'text-green-500' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') }} leading-tight lg:mt-1 lg:mb-0.5">{{ $stat['value'] }}</span>
                        <span class="text-[11px] lg:text-xs text-gray-400 dark:text-gray-500">{{ $stat['subtext'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        @if(App\Filament\Components\Widgets\LatestOrdersTableWidget::canView())
            @livewire(App\Filament\Components\Widgets\LatestOrdersTableWidget::class)
        @endif

        {{ $this->table }}
    </div>

    <x-filament-actions::modals />

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
