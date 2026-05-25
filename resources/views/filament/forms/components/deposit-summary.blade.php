<div
    x-data="{
        paymentMethods: @js(\App\Filament\Forms\Components\PaymentMethodDetails::getPaymentMethodsForView(auth()->user())),
        amount: 0,
        feePercent: 0,
        payAmount: 0,
        calculate() {
            this.amount = Number($get('amount') || 0);
            const selectedPaymentMethodId = Number($get('payment_method_id') || 0);
            const method = this.paymentMethods.find(m => Number(m.id) === selectedPaymentMethodId);
            this.feePercent = method ? Number(method.processing_fee_percent_raw || 0) : 0;
            this.payAmount = this.amount * (1 + this.feePercent / 100);
        }
    }"
    x-init="calculate()"
    x-effect="$get('payment_method_id'); $get('amount'); calculate()"
    x-show="amount > 0 && Number($get('payment_method_id') || 0) > 0"
    style="margin-top: 4px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; font-size: 14px;"
>
    <div style="display: flex; justify-content: space-between; font-weight: 600;">
        <span>
            Pay Tk. <span style="color: #dc2626;" x-text="Number(payAmount || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
        </span>
    </div>
</div>
