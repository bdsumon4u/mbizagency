<div class="space-y-3">
    <style>
        .fi-modal-content .fi-page-header-main-ctn {
            padding: 0 !important;
        }
    </style>

    @livewire(
        $orderHistoryClass,
        [
            'adAccountId' => $record->id,
        ],
        key($table.'-ad-account-order-history-'.$record->id)
    )
</div>
