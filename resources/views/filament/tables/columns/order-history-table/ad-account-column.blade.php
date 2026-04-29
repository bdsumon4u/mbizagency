<div {{ $getExtraAttributeBag() }}>
    @php
        $record = $getRecord();
    @endphp

    <div class="fi-ta-text-has-descriptions fi-ta-text">
        <a
            type="button"
            class="fi-size-sm fi-ta-text-item inline-flex items-center gap-1.5 cursor-pointer text-left transition-colors hover:underline"
            wire:click="mountTableAction('orders', '{{ $record->getKey() }}')"
            wire:loading.class="text-info-600 dark:text-info-400"
            wire:target="mountTableAction('orders', '{{ $record->getKey() }}')"
        >
            <x-heroicon-o-arrow-path
                class="h-4 w-4 animate-spin"
                wire:loading
                wire:target="mountTableAction('orders', '{{ $record->getKey() }}')"
            />

            <span>{{ $getState() }}</span>
        </a>

        <div class="fi-size-sm fi-ta-text-description flex items-center gap-2">
            <a class="hover:underline flex items-center gap-1" href="https://adsmanager.facebook.com/adsmanager/manage/campaigns?act={{ $record->adAccount->act_id }}" target="_blank" rel="noopener noreferrer">
                {{ $record->adAccount->act_id }}<x-heroicon-s-arrow-top-right-on-square class="h-4 w-4 text-blue-600" />
            </a>

            <x-filament::badge :color="$record->status->getColor()" :icon="$record->status->getIcon()" class="order-status-badge">
                {{ $record->status->getLabel() }}
            </x-filament::badge>

            <div>
                Remaining: {{ Number::currency($record->adAccount->spend_cap - $record->adAccount->amount_spent, $record->adAccount->currency) }}
            </div>
        </div>
    </div>
</div>
