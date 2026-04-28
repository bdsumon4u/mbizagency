<?php

namespace App\Filament\Actions;

use App\Actions\ApproveOrderAction;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Pages\OrderHistory;
use App\Mail\NewOrderPendingApprovalMail;
use App\Models\AdAccount;
use App\Models\Admin;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\PriceRateService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use RuntimeException;
use Throwable;

class DepositFundAction
{
    public static function make(): Action
    {
        return Action::make('add_fund')
            ->label('Fund')
            ->tooltip(fn (AdAccount $record): string => 'Add fund to '.$record->name.'.')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->button()
            ->modalWidth(Width::Large)
            ->visible(fn (AdAccount $record): bool => $record->user instanceof User)
            ->schema(function (AdAccount $record): array {
                $priceRateService = app(PriceRateService::class);
                $effectiveRates = $priceRateService->getEffectiveRateRowsForAdAccount($record);
                $assignedPaymentMethods = $record->user->paymentMethods()->active()->orderBy('name')->get();
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
                    Callout::make('Ad Account: '.$record->name.' ('.$record->act_id.')')
                        ->description(function (AdAccount $record): HtmlString {
                            $limit = (float) $record->spend_cap;
                            $spent = (float) $record->amount_spent;
                            $remaining = $limit - $spent;
                            $currency = (string) $record->currency;
                            $lastSyncedAt = $record?->synced_at?->diffForHumans() ?? 'Never';

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
                        ->visibleJs('$get(\'payment_method_id\') !== null'),
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
                            ->numeric()
                            ->minValue(1)
                            ->extraAttributes([
                                'onwheel' => 'return false;',
                            ])
                            ->extraInputAttributes([
                                'x-on:input' => '$dispatch(\'amount-updated\', { amount: Number($el.value || 0) })',
                            ])
                            ->required()
                            ->columnSpan(2),
                    ])
                        ->columns(3)
                        ->visibleJs('$get(\'payment_method_id\') !== null'),
                    View::make('effective_price_rate_feedback')
                        ->view('filament.actions.effective-price-rate-feedback')
                        ->viewData([
                            'rates' => $effectiveRates,
                            'paymentMethods' => $paymentMethodsForView,
                        ])
                        ->visibleJs('$get(\'payment_method_id\') !== null && $get(\'amount\') !== null'),
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
            })
            ->action(function (AdAccount $record, array $data): void {
                try {
                    $admin = self::whichAdmin();
                    $priceRateService = app(PriceRateService::class);
                    $amountCurrency = (string) ($data['currency'] ?? 'usd');
                    $amount = (float) ($data['amount'] ?? 0);
                    $paymentMethodId = (int) ($data['payment_method_id'] ?? 0);
                    $paymentMethod = $record->user?->paymentMethods()
                        ->active()
                        ->whereKey($paymentMethodId)
                        ->first();

                    if (! $paymentMethod instanceof PaymentMethod) {
                        throw new RuntimeException('Please select an assigned payment method.');
                    }

                    $processingFeeMultiplier = 1 + (((float) $paymentMethod->processing_fee_percent) / 100);

                    $pricing = match ($amountCurrency) {
                        'bdt' => (function () use ($priceRateService, $record, $amount, $processingFeeMultiplier): array {
                            $minimumBdt = $priceRateService->getMinimumBdtForAdAccount($record);
                            $netBdtAmount = $amount / $processingFeeMultiplier;

                            if ($minimumBdt !== null && $amount < ($minimumBdt * $processingFeeMultiplier)) {
                                throw new RuntimeException('Minimum deposit amount is '.number_format($minimumBdt * $processingFeeMultiplier, 2).' BDT.');
                            }

                            return $priceRateService->convertBdtToUsdForAdAccount($record, $netBdtAmount);
                        })(),
                        default => $priceRateService->convertUsdToBdtForAdAccount($record, $amount),
                    };

                    $order = DB::transaction(function () use ($record, $data, $admin, $paymentMethod, $pricing): Order {
                        $amountUsd = (float) $pricing['usd_amount'];
                        $amountBdt = (float) $pricing['bdt_amount'];

                        return Order::query()->create([
                            'admin_id' => $admin?->id,
                            'user_id' => $record->user_id,
                            'ad_account_id' => $record->id,
                            'usd_amount' => $amountUsd,
                            'dollar_rate' => $pricing['dollar_rate'],
                            'bdt_amount' => $amountBdt,
                            'source' => $admin ? OrderSource::ADMIN : OrderSource::USER,
                            'status' => OrderStatus::PENDING,
                            'note' => self::formatOrderNote(
                                baseNote: $data['note'] ?? null,
                                paymentMethod: $paymentMethod,
                                bdtAmount: $amountBdt,
                            ),
                            'screenshot' => $data['screenshot'] ?? null,
                        ]);
                    });

                    if (! $order instanceof Order) {
                        throw new RuntimeException('Failed to create order.');
                    }

                    if ($admin) {
                        app(ApproveOrderAction::class)($order, $admin);
                    } else {
                        $order->load(['user', 'adAccount']);
                        self::sendPendingOrderEmails($order);
                    }

                    Notification::make()
                        ->title($admin
                            ? 'Order approved and spend cap synced successfully.'
                            : 'Order submitted and sent to admins for confirmation.')
                        ->success()
                        ->send();
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Failed to submit order.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->successRedirectUrl(fn (AdAccount $record): string => OrderHistory::getUrl());
    }

    private static function whichAdmin(): ?Admin
    {
        if (Filament::getCurrentPanel()?->getAuthGuard() !== 'admin') {
            return null;
        }

        $admin = Filament::auth()->user();

        return $admin instanceof Admin ? $admin : null;
    }

    private static function sendPendingOrderEmails(Order $order): void
    {
        $admins = Admin::query()
            ->whereNotNull('email')
            ->get(['id', 'email']);

        foreach ($admins as $admin) {
            $approveUrl = URL::temporarySignedRoute(
                'filament.admin.orders.approve',
                now()->addDays(2),
                [
                    'order' => $order->id,
                    'admin' => $admin->id,
                ],
            );
            $rejectUrl = URL::temporarySignedRoute(
                'filament.admin.orders.reject',
                now()->addDays(2),
                [
                    'order' => $order->id,
                    'admin' => $admin->id,
                ],
            );

            Mail::to($admin->email)->send(new NewOrderPendingApprovalMail($order, $approveUrl, $rejectUrl));
        }
    }

    private static function formatOrderNote(?string $baseNote, PaymentMethod $paymentMethod, float $bdtAmount): string
    {
        $processingFeePercent = (float) $paymentMethod->processing_fee_percent;
        $processingFeeAmount = $bdtAmount * ($processingFeePercent / 100);
        $totalPayable = $bdtAmount + $processingFeeAmount;

        $paymentSummary = 'Payment Method: '.$paymentMethod->name
            .' (Fee '.number_format($processingFeePercent, 2).'%)'
            .' | Processing Fee: '.number_format($processingFeeAmount, 2).' BDT'
            .' | Total Payable: '.number_format($totalPayable, 2).' BDT';

        if (! $baseNote) {
            return $paymentSummary;
        }

        return $baseNote."\n\n".$paymentSummary;
    }
}
