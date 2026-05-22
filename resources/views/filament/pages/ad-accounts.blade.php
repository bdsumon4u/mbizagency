<x-filament-panels::page class="!pt-0">
    <div class="max-w-7xl mx-auto space-y-3 lg:space-y-6 w-full">
        <!-- Header -->
        <div class="pt-2 sm:pt-0 pb-2">
            <h1 class="text-xs lg:text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                Ad Accounts
            </h1>
        </div>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 lg:gap-6 pb-1">
            <!-- Wallet Balance Card -->
            <div class="sm:col-span-2 flex items-center justify-between p-3 lg:p-5 bg-white dark:bg-gray-900 border border-gray-100 dark:border-white/10 rounded-xl shadow-sm w-full">
                <div class="flex items-center gap-3 lg:gap-4 min-w-0">
                    <div class="flex-shrink-0 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center rounded-lg bg-green-50 dark:bg-green-500/10 text-green-500">
                        <x-heroicon-o-wallet class="w-5 h-5 lg:w-6 lg:h-6" />
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[12px] lg:text-xs font-medium text-gray-500 tracking-wider truncate">Wallet Balance</span>
                        <span class="text-sm lg:text-2xl font-bold text-green-600 leading-tight lg:mt-1 lg:mb-0.5">Tk. {{ number_format(auth()->user()->wallet_balance ?? 0, 2) }}</span>
                        <span class="text-[11px] lg:text-xs text-gray-400 dark:text-gray-500">Available balance</span>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    {{ $this->depositAction }}
                </div>
            </div>

            <!-- Total Ad Accounts Card -->
            <div class="sm:col-span-1 flex items-center gap-3 lg:gap-4 p-3 lg:p-5 bg-white dark:bg-gray-900 border border-gray-100 dark:border-white/10 rounded-xl shadow-sm w-full">
                <div class="flex-shrink-0 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-500">
                    <x-heroicon-o-rectangle-stack class="w-5 h-5 lg:w-6 lg:h-6" />
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-[12px] lg:text-xs font-medium text-gray-500 tracking-wider truncate">Total Ad Accounts</span>
                    <span class="text-sm lg:text-2xl font-bold text-blue-600 leading-tight lg:mt-1 lg:mb-0.5">
                        {{ \App\Models\AdAccount::query()->whereBelongsTo(auth()->user())->count() }}
                    </span>
                    <span class="text-[11px] lg:text-xs text-gray-400 dark:text-gray-500">Associated accounts</span>
                </div>
            </div>

            <!-- Inactive Ad Accounts Card -->
            <div class="sm:col-span-1 flex items-center gap-3 lg:gap-4 p-3 lg:p-5 bg-white dark:bg-gray-900 border border-gray-100 dark:border-white/10 rounded-xl shadow-sm w-full">
                <div class="flex-shrink-0 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center rounded-lg bg-red-50 dark:bg-red-500/10 text-red-500">
                    <x-heroicon-o-x-circle class="w-5 h-5 lg:w-6 lg:h-6" />
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-[12px] lg:text-xs font-medium text-gray-500 tracking-wider truncate">Inactive Accounts</span>
                    <span class="text-sm lg:text-2xl font-bold text-red-600 leading-tight lg:mt-1 lg:mb-0.5">
                        {{ \App\Models\AdAccount::query()->whereBelongsTo(auth()->user())->whereIn('status', \App\Enums\AdAccountStatus::getClosedStatuses())->count() }}
                    </span>
                    <span class="text-[11px] lg:text-xs text-gray-400 dark:text-gray-500">Disabled or closed</span>
                </div>
            </div>
        </div>

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
</x-filament-panels::page>
