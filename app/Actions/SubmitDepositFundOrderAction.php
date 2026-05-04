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
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SubmitDepositFundOrderAction
{
    public function __construct(
        private PriceRateService $priceRateService,
        private ApproveOrderAction $approveOrderAction,
        private SendPendingOrderApprovalEmailsAction $sendPendingOrderApprovalEmailsAction,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{order: Order, approved: bool}
     */
    public function __invoke(AdAccount $adAccount, array $data): array
    {
        $admin = self::whichAdmin();
        $paymentMethod = $this->resolveAssignedPaymentMethod($adAccount, $data);
        $pricing = $this->resolvePricing($adAccount, $paymentMethod, $data);
        $order = $this->createPendingOrder($adAccount, $data, $admin, $pricing, $paymentMethod);

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
            throw new RuntimeException('Please select an assigned payment method.');
        }

        return $paymentMethod;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{usd_amount: float, bdt_amount: float, dollar_rate: float}
     */
    private function resolvePricing(AdAccount $adAccount, PaymentMethod $paymentMethod, array $data): array
    {
        $amountCurrency = (string) ($data['currency'] ?? 'usd');
        $amount = (float) ($data['amount'] ?? 0);
        $processingFeePercent = (float) $paymentMethod->processing_fee_percent;
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
            throw new RuntimeException('Minimum deposit amount is '.number_format($minimumBdt * $processingFeeMultiplier, 2).' BDT.');
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
        PaymentMethod $paymentMethod,
    ): Order {
        return DB::transaction(function () use ($adAccount, $data, $admin, $pricing, $paymentMethod): Order {
            $amountUsd = (float) $pricing['usd_amount'];
            $amountBdt = (float) $pricing['bdt_amount'];
            $processingFeePercent = (float) $paymentMethod->processing_fee_percent;
            $processingFeeAmount = round($amountBdt * ($processingFeePercent / 100), 2);

            return Order::query()->create([
                'admin_id' => $admin?->id,
                'user_id' => $adAccount->user_id,
                'ad_account_id' => $adAccount->id,
                'usd_amount' => $amountUsd,
                'dollar_rate' => $pricing['dollar_rate'],
                'bdt_amount' => $amountBdt,
                'processing_fee' => $processingFeeAmount,
                'balance' => $adAccount->spend_cap - $adAccount->amount_spent,
                'source' => $admin ? OrderSource::ADMIN : OrderSource::USER,
                'status' => OrderStatus::PENDING,
                'note' => $data['note'] ?? null,
                'screenshots' => $data['screenshots'] ?? null,
            ]);
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
}
