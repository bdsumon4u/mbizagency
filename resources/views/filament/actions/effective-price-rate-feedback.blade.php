<div
    x-data="{
        usd: 0,
        rates: @js($rates),
        paymentMethods: @js($paymentMethods),
        selectedPaymentMethod: null,
        selectedRate: null,
        bdtAmount: 0,
        processingFeeBdt: 0,
        totalPayableBdt: 0,
        minimumUsd: 0,
        calculate() {
            const usd = Number(this.usd || 0);
            const selectedPaymentMethodId = Number($get('payment_method_id') || 0);
            this.selectedPaymentMethod = this.paymentMethods.find(method => Number(method.id) === selectedPaymentMethodId) ?? null;
            const sortedByMinAsc = [...this.rates].sort((a, b) => Number(a.min_usd_raw) - Number(b.min_usd_raw));
            const sortedByMinDesc = [...this.rates].sort((a, b) => Number(b.min_usd_raw) - Number(a.min_usd_raw));

            this.minimumUsd = sortedByMinAsc.length ? Number(sortedByMinAsc[0].min_usd_raw) : 0;

            if (!usd || usd <= 0) {
                this.selectedRate = null;
                this.bdtAmount = 0;
                this.processingFeeBdt = 0;
                this.totalPayableBdt = 0;
                return;
            }

            const selected = sortedByMinDesc.find(rate => usd >= Number(rate.min_usd_raw)) ?? null;
            this.selectedRate = selected;
            this.bdtAmount = selected ? usd * Number(selected.dollar_rate_raw) : 0;

            const processingFeePercent = this.selectedPaymentMethod
                ? Number(this.selectedPaymentMethod.processing_fee_percent_raw || 0)
                : 0;
            this.processingFeeBdt = this.bdtAmount * (processingFeePercent / 100);
            this.totalPayableBdt = this.bdtAmount + this.processingFeeBdt;
        },
    }"
    x-init="calculate()"
    x-effect="$get('payment_method_id'); usd; calculate()"
    x-on:usd-updated.window="usd = Number($event.detail?.usd || 0); calculate()"
    style="margin-top: -4px; font-size: 14px;"
>
    <template x-if="minimumUsd > 0 && usd > 0 && usd < minimumUsd">
        <div style="color: #dc2626; margin-bottom: 1rem;">
            Minimum deposit amount is <strong x-text="minimumUsd.toFixed(2)"></strong> USD.
        </div>
    </template>

    <template x-if="selectedPaymentMethod">
        <div style="margin-top: 4px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px;">
            <div style="font-weight: 600;" x-text="selectedPaymentMethod.name"></div>
            <div style="margin-top: 2px; color: #6b7280;">
                Type: <span x-text="selectedPaymentMethod.type"></span>
                | <span style="font-weight: bold; color: #dc2626;">Fee: <span x-text="Number(selectedPaymentMethod.processing_fee_percent_raw || 0).toFixed(2)"></span>%</span>
            </div>
            <div x-show="selectedPaymentMethod.type === 'bank'" style="margin-top: 2px; color: #6b7280;">
                Account Name: <span x-text="selectedPaymentMethod.account_name || '-'"></span>
            </div>
            <div style="margin-top: 2px; color: #6b7280;">
                Account Number: <span x-text="selectedPaymentMethod.account_number || '-'"></span>
            </div>
            <div x-show="selectedPaymentMethod.type === 'bank'" style="margin-top: 2px; color: #6b7280;">
                Branch: <span x-text="selectedPaymentMethod.branch || '-'"></span>
            </div>
            <div x-show="selectedPaymentMethod.instructions !== null && selectedPaymentMethod.instructions !== ''" style="margin-top: 2px; color: #6b7280;">
                Instructions:
                <div style="margin-top: 2px; color: #dc2626;" x-text="selectedPaymentMethod.instructions"></div>
            </div>
        </div>
    </template>

    <template x-if="selectedRate && usd >= minimumUsd">
        <div style="margin-top: 1rem;">
            <div style="color: #6b7280;">
                Applied: <span x-text="selectedRate.type"></span> (Min USD <span x-text="selectedRate.min_usd"></span> @ <span x-text="selectedRate.dollar_rate"></span>)
            </div>
            <div style="margin-top: 2px;">
                Equivalent BDT:
                <strong x-text="`Tk. ${bdtAmount.toFixed(2)}`"></strong>
            </div>
            <div style="margin-top: 2px;">
                Processing Fee:
                <strong x-text="`Tk. ${processingFeeBdt.toFixed(2)}`"></strong>
            </div>
            <div style="margin-top: 2px;">
                Total Payable:
                <strong x-text="`Tk. ${totalPayableBdt.toFixed(2)}`"></strong>
            </div>
        </div>
    </template>
</div>
