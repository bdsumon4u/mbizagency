<div class="space-y-3">
    @livewire(
        $orderHistoryClass,
        [
            'adAccountId' => $record->id,
        ],
        key($table.'-ad-account-order-history-'.$record->id)
    )
</div>
