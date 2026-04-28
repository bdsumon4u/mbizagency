<div
    x-data="{
        paymentMethods: @js($paymentMethods),
        selectedPaymentMethod: null,
        async copy(value, label) {
            if (! value) {
                return;
            }

            await navigator.clipboard.writeText(String(value));

            new FilamentNotification()
                .title(`${label} copied to clipboard`)
                .success()
                .send();
        },
        calculate() {
            const selectedPaymentMethodId = Number($get('payment_method_id') || 0);
            this.selectedPaymentMethod = this.paymentMethods.find(method => Number(method.id) === selectedPaymentMethodId) ?? null;
        },
    }"
    x-init="calculate()"
    x-effect="$get('payment_method_id'); calculate()"
>
    <template x-if="selectedPaymentMethod">
        <div style="margin-top: 4px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px;">
            <div style="font-weight: 600;" x-text="selectedPaymentMethod.name"></div>
            <div style="margin-top: 2px; color: #6b7280;">
                Type: <span x-text="selectedPaymentMethod.type"></span>
                | <span style="font-weight: bold; color: #dc2626;">Fee: <span x-text="Number(selectedPaymentMethod.processing_fee_percent_raw || 0).toFixed(2)"></span>%</span>
            </div>
            <div x-show="selectedPaymentMethod.type === 'Bank'" style="margin-top: 2px; color: #6b7280;">
                Account Name: <span x-text="selectedPaymentMethod.account_name || '-'"></span>
                <button
                    x-show="selectedPaymentMethod.account_name"
                    x-on:click.prevent="copy(selectedPaymentMethod.account_name, 'Account name')"
                    type="button"
                    style="margin-left: 6px; color: #2563eb; font-size: 12px; font-weight: 600; border: 1px solid; padding: 1px 2px; border-radius: 2px;"
                >
                    Copy
                </button>
            </div>
            <div style="margin-top: 2px; color: #6b7280;">
                Account Number: <span x-text="selectedPaymentMethod.account_number || '-'"></span>
                <button
                    x-show="selectedPaymentMethod.account_number"
                    x-on:click.prevent="copy(selectedPaymentMethod.account_number, 'Account number')"
                    type="button"
                    style="margin-left: 6px; color: #2563eb; font-size: 12px; font-weight: 600; border: 1px solid; padding: 1px 2px; border-radius: 2px;"
                >
                    Copy
                </button>
            </div>
            <div x-show="selectedPaymentMethod.type === 'Bank'" style="margin-top: 2px; color: #6b7280;">
                Branch: <span x-text="selectedPaymentMethod.branch || '-'"></span>
            </div>
            <div x-show="selectedPaymentMethod.instructions !== null && selectedPaymentMethod.instructions !== ''" style="margin-top: 2px; color: #6b7280;">
                Instructions:
                <div style="margin-top: 2px; color: #dc2626;" x-text="selectedPaymentMethod.instructions"></div>
            </div>
        </div>
    </template>
</div>
