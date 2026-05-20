<?php

namespace App\Filament\Actions\DepositFund;

use App\Filament\Forms\Components\PaymentMethodDetails;
use App\Models\AdAccount;
use App\Models\User;
use App\Services\PriceRateService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
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

        $paymentMethodsForView = PaymentMethodDetails::getPaymentMethodsForView($adAccount->user);

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
            Radio::make('payment_source')
                ->label('Payment Source')
                ->options(function () use ($adAccount) {
                    $balance = $adAccount->user?->wallet_balance ?? 0;

                    return [
                        'payment_method' => 'Saved Payment Method',
                        'wallet' => 'My Wallet (Tk. '.$balance.')',
                    ];
                })
                ->default('payment_method')
                ->inline()
                ->live()
                ->required(),
            Select::make('payment_method_id')
                ->label('Payment Method')
                ->placeholder('Select a payment method')
                ->options($paymentMethodOptions)
                ->searchable()
                ->preload()
                ->required(fn (Get $get) => $get('payment_source') === 'payment_method')
                ->visibleJs('$get(\'payment_source\') === \'payment_method\''),
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
                ->visibleJs('!! $get(\'payment_method_id\') || $get(\'payment_source\') === \'wallet\''),
            View::make('effective_price_rate_feedback')
                ->view('filament.actions.effective-price-rate-feedback')
                ->viewData([
                    'rates' => $effectiveRates,
                    'paymentMethods' => $paymentMethodsForView,
                ])
                ->visibleJs('(!! $get(\'payment_method_id\') || $get(\'payment_source\') === \'wallet\') && !! $get(\'amount\')'),
            PaymentMethodDetails::make('selected_payment_method_details')
                ->paymentMethods($paymentMethodsForView)
                ->visibleJs('!! $get(\'payment_method_id\') && $get(\'payment_source\') === \'payment_method\''),
            ViewField::make('screenshots')
                ->view('filament.forms.components.custom-file-upload')
                ->required(fn (Get $get) => Filament::getCurrentPanel()?->getId() !== 'admin' && $get('payment_source') === 'payment_method')
                ->visibleJs('$get(\'payment_source\') === \'payment_method\''),
            Textarea::make('note')
                ->label('Note (optional)')
                ->maxLength(500)
                ->visible(fn () => Filament::getCurrentPanel()?->getId() === 'admin'),
        ];
    }
}
