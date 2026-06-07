<x-filament-panels::page>
    {{ $this->table }}

    <script>
        const balance = document.querySelector('.fi-ta')?.getAttribute('data-balance');
        if (balance) {
            const actions = document.querySelector('.fi-ta-header-toolbar .fi-ta-actions');
            actions.innerHTML = `<div class="fi-ta-actions-item-wrap">
                Balance: Tk. <span class="text-success">${balance}</span>
            </div>` + actions.innerHTML;
        }
    </script>
</x-filament-panels::page>
