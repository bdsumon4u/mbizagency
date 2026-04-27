<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\BusinessManager;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AdAccountSpendCapService
{
    private const GRAPH_API_VERSION = 'v25.0';

    public function sync(AdAccount $adAccount): void
    {
        $businessManager = $adAccount->businessManager;

        if (! $businessManager instanceof BusinessManager) {
            throw new RuntimeException('Business manager not found for this ad account.');
        }

        $validation = $this->validateSpendLimit($adAccount->spend_cap, (string) $adAccount->currency);

        if (! $validation['valid']) {
            throw new RuntimeException(implode("\n", $validation['errors']));
        }

        $response = $this->setSpendLimit(
            $businessManager,
            (string) $adAccount->act_id,
            $adAccount->spend_cap,
        );

        if (! ($response['success'] ?? false)) {
            throw new RuntimeException((string) ($response['message'] ?? 'Unknown spend cap sync error.'));
        }

        $adAccount->update([
            'synced_at' => now(),
        ]);
    }

    private function validateSpendLimit(float $targetSpendLimit, string $currency): array
    {
        $errors = [];

        if ($targetSpendLimit <= 0) {
            $errors[] = 'Spend limit must be greater than 0.';
        }

        if ($currency !== 'USD') {
            $errors[] = 'Currency must be USD.';
        }

        if ($targetSpendLimit > 30_000) {
            $errors[] = 'Spend limit must be less than 30,000 USD.';
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }

    private function setSpendLimit(BusinessManager $businessManager, string $actId, float $targetSpendLimit): array
    {
        $adAccountId = str_starts_with($actId, 'act_') ? $actId : 'act_'.$actId;
        $spendCap = (int) round($targetSpendLimit);

        $response = Http::post('https://graph.facebook.com/'.self::GRAPH_API_VERSION."/{$adAccountId}", $data = [
            'access_token' => $businessManager->access_token,
            'spend_cap' => $spendCap,
        ]);

        info('setSpendLimit response: '.json_encode($response->json()), $data);

        if ($response->failed()) {
            return [
                'success' => false,
                'message' => $response->json('error.message') ?? $response->json('error_description') ?? 'Unknown spend cap sync error.',
            ];
        }

        return [
            'success' => true,
            'spend_limit' => $spendCap,
        ];
    }
}
