<div
    x-data="{
        amount: 0,
        rates: @js($rates),
        paymentMethods: @js($paymentMethods),
        selectedPaymentMethod: null,
        selectedRate: null,
        amountCurrency: 'usd',
        usdAmount: 0,
        bdtAmount: 0,
        processingFeeBdt: 0,
        totalPayableBdt: 0,
        minimumUsd: 0,
        minimumBdt: 0,
        calculate() {
            const amount = Number(this.amount || 0);
            const selectedPaymentMethodId = Number($get('payment_method_id') || 0);
            this.amountCurrency = String($get('currency') || 'usd');
            this.selectedPaymentMethod = this.paymentMethods.find(method => Number(method.id) === selectedPaymentMethodId) ?? null;
            const sortedByMinAsc = [...this.rates].sort((a, b) => Number(a.min_usd_raw) - Number(b.min_usd_raw));
            const sortedByMinDesc = [...this.rates].sort((a, b) => Number(b.min_usd_raw) - Number(a.min_usd_raw));
            const processingFeePercent = Number(this.selectedPaymentMethod.processing_fee_percent_raw || 0);
            const processingFeeMultiplier = 1 + (processingFeePercent / 100);

            this.minimumUsd = sortedByMinAsc.length ? Number(sortedByMinAsc[0].min_usd_raw) : 0;
            this.minimumBdt = sortedByMinAsc.length
                ? Number(sortedByMinAsc[0].min_usd_raw) * Number(sortedByMinAsc[0].dollar_rate_raw) * processingFeeMultiplier
                : 0;

            if (!amount || amount <= 0) {
                this.selectedRate = null;
                this.usdAmount = 0;
                this.bdtAmount = 0;
                this.processingFeeBdt = 0;
                this.totalPayableBdt = 0;
                return;
            }

            if (this.amountCurrency === 'bdt') {
                const netBdtAmount = amount / processingFeeMultiplier;
                const selected = sortedByMinDesc.find(rate => (netBdtAmount / Number(rate.dollar_rate_raw)) >= Number(rate.min_usd_raw)) ?? null;
                this.selectedRate = selected;
                this.bdtAmount = netBdtAmount;
                this.usdAmount = selected ? netBdtAmount / Number(selected.dollar_rate_raw) : 0;
            } else {
                const selected = sortedByMinDesc.find(rate => amount >= Number(rate.min_usd_raw)) ?? null;
                this.selectedRate = selected;
                this.usdAmount = amount;
                this.bdtAmount = selected ? amount * Number(selected.dollar_rate_raw) : 0;
            }

            this.processingFeeBdt = this.bdtAmount * (processingFeePercent / 100);
            this.totalPayableBdt = this.bdtAmount + this.processingFeeBdt;
        },
    }"
    x-init="calculate()"
    x-effect="$get('payment_method_id'); $get('currency'); amount; calculate()"
    x-on:amount-updated.window="amount = Number($event.detail?.amount || 0); calculate()"
    style="margin-top: -4px; font-size: 14px;"
>
    <template x-if="
        (amountCurrency === 'bdt' && minimumBdt > 0 && amount > 0 && amount < minimumBdt)
        || (amountCurrency !== 'bdt' && minimumUsd > 0 && amount > 0 && amount < minimumUsd)
    ">
        <div style="color: #dc2626;">
            Minimum deposit amount is
            <strong x-text="amountCurrency === 'bdt' ? minimumBdt.toFixed(2) + ' BDT' : minimumUsd.toFixed(2) + ' USD'"></strong>.
        </div>
    </template>

    <template x-if="selectedRate && usdAmount >= minimumUsd">
        <div>
            <div style="color: #6b7280;">
                Applied: <span x-text="selectedRate.type"></span> (Min USD <span x-text="selectedRate.min_usd"></span> @ <span x-text="selectedRate.dollar_rate"></span>)
            </div>
            <div style="margin-top: 2px;">
                Equivalent <span x-text="amountCurrency === 'bdt' ? 'USD' : 'BDT'"></span>:
                <strong x-text="amountCurrency === 'bdt' ? `${usdAmount.toFixed(2)} USD` : `Tk. ${bdtAmount.toFixed(2)}`"></strong>
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
