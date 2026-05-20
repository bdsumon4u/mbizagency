<div class="space-y-3">
    @livewire(
        $orderHistoryClass,
        [
            'userId' => $record->id,
        ],
        key($table.'-user-order-history-'.$record->id)
    )
</div>
