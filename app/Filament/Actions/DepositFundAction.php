<?php

namespace App\Filament\Actions;

use App\Actions\ApproveOrderAction;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Mail\NewOrderPendingApprovalMail;
use App\Models\AdAccount;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use App\Services\PriceRateService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\View;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Throwable;

class DepositFundAction
{
    public static function make(): Action
    {
        return Action::make('add_fund')
            ->label('Add Fund')
            ->tooltip(fn (AdAccount $record): string => 'Add fund to '.$record->name.'.')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->button()
            ->modalWidth(Width::Large)
            ->visible(fn (AdAccount $record): bool => $record->user instanceof User)
            ->schema(function (AdAccount $record): array {
                $priceRateService = app(PriceRateService::class);
                $effectiveRates = $priceRateService->getEffectiveRateRowsForAdAccount($record);

                return [
                    View::make('effective_price_rates_table')
                        ->view('filament.actions.effective-price-rates-table')
                        ->viewData([
                            'rates' => $effectiveRates,
                        ]),
                    Callout::make('Ad Account: '.$record->name.' ('.$record->act_id.')')
                        ->icon('heroicon-o-information-circle')
                        ->description('Current ad account balance: '.number_format((float) $record->balance, 2).' '.$record->currency)
                        ->info(),
                    TextInput::make('usd_amount')
                        ->label('Amount (USD)')
                        ->numeric()
                        ->minValue(1)
                        ->extraAttributes([
                            'onwheel' => 'return false;',
                        ])
                        ->extraInputAttributes([
                            'x-on:input' => '$dispatch(\'usd-updated\', { usd: Number($el.value || 0) })',
                        ])
                        ->required(),
                    View::make('effective_price_rate_feedback')
                        ->view('filament.actions.effective-price-rate-feedback')
                        ->viewData([
                            'rates' => $effectiveRates,
                        ]),
                    FileUpload::make('screenshot')
                        ->label('Screenshot')
                        ->image()
                        ->disk('public')
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
                    $amountUsd = (float) $data['usd_amount'];
                    $minimumUsd = $priceRateService->getMinimumUsdForAdAccount($record);

                    if ($minimumUsd !== null && $amountUsd < $minimumUsd) {
                        throw new RuntimeException('Minimum deposit amount is '.number_format($minimumUsd, 2).' USD.');
                    }

                    $order = DB::transaction(function () use ($record, $data, $admin): Order {
                        $amountUsd = (float) $data['usd_amount'];
                        $pricing = app(PriceRateService::class)->convertUsdToBdtForAdAccount($record, $amountUsd);
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
                            'note' => $data['note'] ?? null,
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
            });
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
}
