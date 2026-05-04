<?php

namespace App\Filament\Actions\DepositFund;

use App\Models\AdAccount;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\PriceRateService;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Illuminate\Support\HtmlString;

final class DepositFundFormSchema
{
    public function __construct(private PriceRateService $priceRateService) {}

    /**
     * @return array<Component>
     */
    public function build(AdAccount $adAccount): array
    {
        if (! $adAccount->user instanceof User) {
            return [];
        }

        $effectiveRates = $this->priceRateService->getEffectiveRateRowsForAdAccount($adAccount);
        $assignedPaymentMethods = $adAccount->user->paymentMethods()->active()->orderBy('name')->get();

        $paymentMethodOptions = $assignedPaymentMethods
            ->pluck('name', 'id')
            ->toArray();

        $paymentMethodsForView = $assignedPaymentMethods
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

        return [
            Callout::make('Ad Account ID: '.$adAccount->act_id)
                ->description(function (AdAccount $record): HtmlString {
                    $limit = (float) $record->spend_cap;
                    $spent = (float) $record->amount_spent;
                    $remaining = $limit - $spent;
                    $currency = (string) $record->currency;
                    $lastSyncedAt = $record->synced_at?->diffForHumans() ?? 'Never';

                    return new HtmlString(
                        '<ul style="margin: 0.25rem 0 0; padding: 0; list-style: none; line-height: 1.45; display: flex; flex-wrap: wrap; gap: 0.25rem 0.75rem;">'
                        .'<li><strong>Limit:</strong> '.number_format($limit, 2).' '.$currency.'</li>'
                        .'<li><strong>Spent:</strong> '.number_format($spent, 2).' '.$currency.'</li>'
                        .'<li><strong>Remaining:</strong> '.number_format($remaining, 2).' '.$currency.'</li>'
                        .'<li><strong>Synced:</strong> '.$lastSyncedAt.'</li>'
                        .'</ul>',
                    );
                })
                ->info()
                ->icon(null),
            View::make('deposit_reference_sections')
                ->view('filament.actions.deposit-reference-sections')
                ->viewData([
                    'rates' => $effectiveRates,
                ]),
            Select::make('payment_method_id')
                ->label('Payment Method')
                ->placeholder('Select a payment method')
                ->options($paymentMethodOptions)
                ->searchable()
                ->preload()
                ->required(),
            View::make('selected_payment_method_details')
                ->view('filament.actions.selected-payment-method-details')
                ->viewData([
                    'paymentMethods' => $paymentMethodsForView,
                ])
                ->visibleJs('!! $get(\'payment_method_id\')'),
            Group::make([
                Radio::make('currency')
                    ->options([
                        'usd' => 'USD',
                        'bdt' => 'BDT',
                    ])
                    ->default('usd')
                    ->inline()
                    ->required(),
                TextInput::make('amount')
                    ->hint(new HtmlString('<span x-text="$get(\'currency\') === \'bdt\' ? \'BDT\' : \'USD\'"></span>'))
                    ->numeric()
                    ->minValue(1)
                    ->extraAttributes([
                        'onwheel' => 'return false;',
                    ])
                    ->extraInputAttributes([
                        'x-bind:placeholder' => '$get(\'currency\') === \'bdt\' ? \'Enter BDT amount\' : \'Enter USD amount\'',
                        'x-on:input' => '$dispatch(\'amount-updated\', { amount: Number($el.value || 0) })',
                    ])
                    ->required()
                    ->columnSpan(2),
            ])
                ->columns(3)
                ->visibleJs('!! $get(\'payment_method_id\')'),
            View::make('effective_price_rate_feedback')
                ->view('filament.actions.effective-price-rate-feedback')
                ->viewData([
                    'rates' => $effectiveRates,
                    'paymentMethods' => $paymentMethodsForView,
                ])
                ->visibleJs('!! $get(\'payment_method_id\') && !! $get(\'amount\')'),
            FileUpload::make('screenshots')
                ->image()
                ->disk('public')
                ->multiple()
                ->directory('orders/screenshots')
                ->visibility('public')
                ->optimize('webp', 75)
                ->automaticallyResizeImagesMode('contain')
                ->maxImageWidth('300')
                ->maxImageHeight('500')
                ->automaticallyUpscaleImagesWhenResizing(false)
                ->required(Filament::getCurrentPanel()?->getId() !== 'admin'),
            Textarea::make('note')
                ->label('Note (optional)')
                ->maxLength(500),
        ];
    }
}
