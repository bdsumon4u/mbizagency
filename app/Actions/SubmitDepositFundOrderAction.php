<?php

namespace App\Actions;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\AdAccount;
use App\Models\Admin;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\PriceRateService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class SubmitDepositFundOrderAction
{
    public function __construct(
        private PriceRateService $priceRateService,
        private ApproveOrderAction $approveOrderAction,
        private SendPendingOrderApprovalEmailsAction $sendPendingOrderApprovalEmailsAction,
    ) {}

    public function __invoke(AdAccount $adAccount, array $data): array
    {
        $admin = self::whichAdmin();
        $paymentSource = $data['payment_source'] ?? 'payment_method';
        $paymentMethod = $paymentSource === 'payment_method'
            ? $this->resolveAssignedPaymentMethod($adAccount, $data)
            : null;

        $pricing = $this->resolvePricing($adAccount, $paymentMethod, $data);

        if ($paymentSource === 'wallet') {
            $processingFeePercent = $paymentMethod ? (float) $paymentMethod->processing_fee_percent : 0;
            $processingFeeAmount = round($pricing['bdt_amount'] * ($processingFeePercent / 100), 2);
            $totalPayable = $pricing['bdt_amount'] + $processingFeeAmount;

            if ($adAccount->user->wallet_balance < $totalPayable) {
                Notification::make()
                    ->title('Insufficient wallet balance.')
                    ->body('You need '.number_format($totalPayable, 2).' BDT.')
                    ->danger()
                    ->send();

                throw new Halt;
            }
        }

        $order = $this->createPendingOrder($adAccount, $data, $admin, $pricing, $paymentMethod, $paymentSource);

        if ($admin instanceof Admin) {
            $this->approveOrderAction->__invoke($order, $admin);

            return [
                'order' => $order->refresh(),
                'approved' => true,
            ];
        }

        $order->load(['user', 'adAccount']);
        $this->sendPendingOrderApprovalEmailsAction->__invoke($order);

        return [
            'order' => $order,
            'approved' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveAssignedPaymentMethod(AdAccount $adAccount, array $data): PaymentMethod
    {
        $paymentMethodId = (int) ($data['payment_method_id'] ?? 0);

        $paymentMethod = $adAccount->user?->paymentMethods()
            ->active()
            ->whereKey($paymentMethodId)
            ->first();

        if (! $paymentMethod instanceof PaymentMethod) {
            Notification::make()
                ->title('Please select an assigned payment method.')
                ->danger()
                ->send();

            throw new Halt;
        }

        return $paymentMethod;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{usd_amount: float, bdt_amount: float, dollar_rate: float}
     */
    private function resolvePricing(AdAccount $adAccount, ?PaymentMethod $paymentMethod, array $data): array
    {
        $amountCurrency = (string) ($data['currency'] ?? 'usd');
        $amount = (float) ($data['amount'] ?? 0);
        $processingFeePercent = $paymentMethod ? (float) $paymentMethod->processing_fee_percent : 0;
        $processingFeeMultiplier = 1 + ($processingFeePercent / 100);

        return match ($amountCurrency) {
            'bdt' => $this->convertBdtToUsd($adAccount, $amount, $processingFeeMultiplier),
            default => $this->priceRateService->convertUsdToBdtForAdAccount($adAccount, $amount),
        };
    }

    /**
     * @return array{usd_amount: float, bdt_amount: float, dollar_rate: float}
     */
    private function convertBdtToUsd(AdAccount $adAccount, float $amount, float $processingFeeMultiplier): array
    {
        $minimumBdt = $this->priceRateService->getMinimumBdtForAdAccount($adAccount);
        $netBdtAmount = $amount / $processingFeeMultiplier;

        if ($minimumBdt !== null && $amount < ($minimumBdt * $processingFeeMultiplier)) {
            Notification::make()
                ->title('Minimum deposit amount is '.number_format($minimumBdt * $processingFeeMultiplier, 2).' BDT.')
                ->danger()
                ->send();

            throw new Halt;
        }

        return $this->priceRateService->convertBdtToUsdForAdAccount($adAccount, $netBdtAmount);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{usd_amount: float, bdt_amount: float, dollar_rate: float}  $pricing
     */
    private function createPendingOrder(
        AdAccount $adAccount,
        array $data,
        ?Admin $admin,
        array $pricing,
        ?PaymentMethod $paymentMethod,
        string $paymentSource,
    ): Order {
        return DB::transaction(function () use ($adAccount, $data, $admin, $pricing, $paymentMethod, $paymentSource): Order {
            $amountUsd = (float) $pricing['usd_amount'];
            $amountBdt = (float) $pricing['bdt_amount'];
            $processingFeePercent = $paymentMethod ? (float) $paymentMethod->processing_fee_percent : 0;
            $processingFeeAmount = round($amountBdt * ($processingFeePercent / 100), 2);

            return Order::query()->create([
                'admin_id' => $admin?->id,
                'user_id' => $adAccount->user_id,
                'ad_account_id' => $adAccount->id,
                'usd_amount' => $amountUsd,
                'dollar_rate' => $pricing['dollar_rate'],
                'bdt_amount' => $amountBdt,
                'processing_fee' => $processingFeeAmount,
                'payment_source' => $paymentSource,
                'balance' => $adAccount->spend_cap - $adAccount->amount_spent,
                'source' => $admin ? OrderSource::ADMIN : OrderSource::USER,
                'status' => OrderStatus::PENDING,
                'note' => $data['note'] ?? null,
                'screenshots' => $this->handleScreenshots($data['screenshots'] ?? []),
            ]);
        });
    }

    private function handleScreenshots(array $screenshots): ?array
    {
        if (empty($screenshots)) {
            return null;
        }

        $finalPaths = [];
        foreach ($screenshots as $screenshot) {
            // Handle UploadedFile objects (e.g. TemporaryUploadedFile from Livewire)
            if ($screenshot instanceof UploadedFile) {
                try {
                    $path = $screenshot->store('orders/screenshots', 'public');
                    if ($path) {
                        $finalPaths[] = $path;
                    }
                } catch (\Throwable $e) {
                    report($e);
                }

                continue;
            }

            // Ensure screenshot is a string for further checks
            if (! is_string($screenshot)) {
                continue;
            }

            // If it's already a permanent path, keep it
            if (! str_starts_with($screenshot, 'livewire-file:')) {
                $finalPaths[] = $screenshot;

                continue;
            }

            // Handle Livewire temporary upload from string (if applicable)
            try {
                $tempPath = str_replace('livewire-file:', '', $screenshot);
                $newPath = 'orders/screenshots/'.basename($tempPath);

                if (Storage::disk('local')->exists('livewire-tmp/'.$tempPath)) {
                    Storage::disk('public')->put(
                        $newPath,
                        Storage::disk('local')->get('livewire-tmp/'.$tempPath)
                    );
                    $finalPaths[] = $newPath;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return ! empty($finalPaths) ? $finalPaths : null;
    }

    private static function whichAdmin(): ?Admin
    {
        if (Filament::getCurrentPanel()?->getAuthGuard() !== 'admin') {
            return null;
        }

        $admin = Filament::auth()->user();

        return $admin instanceof Admin ? $admin : null;
    }
}
