<div
    x-data="{
        usd: 0,
        rates: @js($rates),
        selectedRate: null,
        bdtAmount: 0,
        calculate() {
            const usd = Number(this.usd || 0);
            if (!usd || usd <= 0) {
                this.selectedRate = null;
                this.bdtAmount = 0;
                return;
            }

            const sortedRates = [...this.rates].sort((a, b) => Number(b.min_usd_raw) - Number(a.min_usd_raw));
            let selected = sortedRates.find(rate => usd >= Number(rate.min_usd_raw));
            if (!selected && sortedRates.length > 0) {
                selected = sortedRates[sortedRates.length - 1];
            }

            this.selectedRate = selected ?? null;
            if (!this.selectedRate) {
                this.bdtAmount = 0;
                return;
            }

            this.bdtAmount = usd * Number(this.selectedRate.dollar_rate_raw);
        },
    }"
    x-on:usd-updated.window="usd = Number($event.detail?.usd || 0); calculate()"
>
    <div style="margin-bottom: 8px; font-size: 14px; font-weight: 600;">
        Effective Price Rates
    </div>

    @if (blank($rates))
        <div style="font-size: 14px; color: #6b7280;">
            No effective price rate found. Please contact admin.
        </div>
    @else
        <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 12px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead style="background: #f9fafb;">
                    <tr>
                        <th style="padding: 8px 12px; text-align: left; border-bottom: 1px solid #e5e7eb;">Rate Type</th>
                        <th style="padding: 8px 12px; text-align: right; border-bottom: 1px solid #e5e7eb;">Min USD</th>
                        <th style="padding: 8px 12px; text-align: right; border-bottom: 1px solid #e5e7eb;">Dollar Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rates as $rate)
                        <tr>
                            <td style="padding: 8px 12px; border-top: 1px solid #e5e7eb;">{{ $rate['type'] }}</td>
                            <td style="padding: 8px 12px; border-top: 1px solid #e5e7eb; text-align: right;">{{ $rate['min_usd'] }}</td>
                            <td style="padding: 8px 12px; border-top: 1px solid #e5e7eb; text-align: right;">{{ $rate['dollar_rate'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div style="margin-top: 10px; font-size: 14px;">
        <div>
            Equivalent BDT:
            <strong x-text="bdtAmount.toFixed(2)"></strong>
        </div>
        <template x-if="selectedRate">
            <div style="color: #6b7280; margin-top: 2px;">
                Applied: <span x-text="selectedRate.type"></span> (Min USD <span x-text="selectedRate.min_usd"></span> @ <span x-text="selectedRate.dollar_rate"></span>)
            </div>
        </template>
    </div>
</div>
