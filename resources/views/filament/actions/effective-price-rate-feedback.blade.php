<div
    x-data="{
        usd: 0,
        rates: @js($rates),
        selectedRate: null,
        bdtAmount: 0,
        minimumUsd: 0,
        calculate() {
            const usd = Number(this.usd || 0);
            const sortedByMinAsc = [...this.rates].sort((a, b) => Number(a.min_usd_raw) - Number(b.min_usd_raw));
            const sortedByMinDesc = [...this.rates].sort((a, b) => Number(b.min_usd_raw) - Number(a.min_usd_raw));

            this.minimumUsd = sortedByMinAsc.length ? Number(sortedByMinAsc[0].min_usd_raw) : 0;

            if (!usd || usd <= 0) {
                this.selectedRate = null;
                this.bdtAmount = 0;
                return;
            }

            const selected = sortedByMinDesc.find(rate => usd >= Number(rate.min_usd_raw)) ?? null;
            this.selectedRate = selected;
            this.bdtAmount = selected ? usd * Number(selected.dollar_rate_raw) : 0;
        },
    }"
    x-init="calculate()"
    x-on:usd-updated.window="usd = Number($event.detail?.usd || 0); calculate()"
    style="margin-top: -4px; font-size: 14px;"
>
    <template x-if="minimumUsd > 0 && usd > 0 && usd < minimumUsd">
        <div style="color: #dc2626; margin-top: 2px;">
            Minimum deposit amount is <strong x-text="minimumUsd.toFixed(2)"></strong> USD.
        </div>
    </template>

    <template x-if="selectedRate && usd >= minimumUsd">
        <div>
            <div>
                Equivalent BDT:
                <strong x-text="bdtAmount.toFixed(2)"></strong>
            </div>
            <div style="color: #6b7280; margin-top: 2px;">
                Applied: <span x-text="selectedRate.type"></span> (Min USD <span x-text="selectedRate.min_usd"></span> @ <span x-text="selectedRate.dollar_rate"></span>)
            </div>
        </div>
    </template>
</div>
