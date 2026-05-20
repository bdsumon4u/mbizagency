<?php

namespace App\Filament\Forms\Components;

use App\Models\PaymentMethod;
use Filament\Forms\Components\ViewField;

class PaymentMethodDetails extends ViewField
{
    protected string $view = 'filament.actions.selected-payment-method-details';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
    }

    public function paymentMethods(array|\Closure $paymentMethods): static
    {
        $this->viewData(['paymentMethods' => $paymentMethods]);

        return $this;
    }

    public static function getPaymentMethodsForView($user): array
    {
        if (! $user) {
            return [];
        }

        return $user->paymentMethods()->active()->orderBy('name')->get()
            ->map(fn (PaymentMethod $paymentMethod): array => [
                'id' => $paymentMethod->id,
                'name' => $paymentMethod->name,
                'type' => $paymentMethod->type,
                'processing_fee_percent' => number_format((float) $paymentMethod->processing_fee_percent, 2),
                'processing_fee_percent_raw' => (float) $paymentMethod->processing_fee_percent,
                'account_name' => $paymentMethod->account_name,
                'account_number' => $paymentMethod->account_number,
                'branch' => $paymentMethod->branch,
                'instructions' => $paymentMethod->instructions,
            ])
            ->values()
            ->all();
    }
}
