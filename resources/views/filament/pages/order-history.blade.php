<x-filament-panels::page class="!pt-0">
    <div class="max-w-7xl mx-auto space-y-3 lg:space-y-6 w-full">
        @unless($this->adAccountId)
        <!-- Header -->
        <div class="pt-2 sm:pt-0">
            <h1 class="text-xs lg:text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Order History</h1>
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
