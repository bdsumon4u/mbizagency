<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdAccountDisableReason;
use App\Enums\AdAccountStatus;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use Exception;
use Illuminate\Support\Facades\Http;

final class FacebookAdAccountService
{
    /**
     * @throws Exception
     */
    public function importFromBusinessManager(BusinessManager $businessManager): int
    {
        $adAccounts = $this->fetchBusinessManagerAdAccounts($businessManager);

        $importedCount = 0;

        foreach ($adAccounts as $account) {
            $rawId = (string) ($account['id'] ?? '');
            $actId = str_replace('act_', '', $rawId);

            if ($actId === '') {
                continue;
            }

            AdAccount::query()->updateOrCreate(
                ['act_id' => $actId],
                [
                    'business_manager_id' => $businessManager->id,
                    'name' => (string) ($account['name'] ?? $actId),
                    'status' => (int) ($account['account_status'] ?? AdAccountStatus::ACTIVE->value),
                    'currency' => (string) ($account['currency'] ?? 'USD'),
                    'balance' => (int) ($account['balance'] ?? 0),
                    'payment_method' => (string) ($account['funding_source_details']['display_string'] ?? ''),
                    'spend_cap' => isset($account['spend_cap']) ? (int) $account['spend_cap'] : null,
                    'timezone' => (string) ($account['timezone_name'] ?? ''),
                    'disable_reason' => AdAccountDisableReason::tryFrom((int) ($account['disable_reason'] ?? 0)),
                    'synced_at' => now(),
                ],
            );

            $importedCount++;
        }

        return $importedCount;
    }

    public function validateSpendLimit(float $spendLimit, string $currency = 'USD'): array
    {
        $errors = [];

        if ($spendLimit <= 0) {
            $errors[] = "Spend cap must be greater than 0 {$currency}.";
        }

        if ($spendLimit > 1000000) {
            $errors[] = "Spend cap cannot exceed 1,000,000 {$currency}.";
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function setSpendLimit(BusinessManager $businessManager, string $actId, float $spendLimit): array
    {
        $adAccountId = str_starts_with($actId, 'act_') ? $actId : 'act_'.$actId;

        $response = Http::post("https://graph.facebook.com/v21.0/{$adAccountId}", [
            'access_token' => $businessManager->access_token,
            'spend_cap' => (int) round($spendLimit),
        ]);

        if ($response->failed()) {
            return [
                'success' => false,
                'message' => $response->json('error.message')
                    ?? $response->json('error_description')
                    ?? 'Failed to update spend cap on Facebook.',
            ];
        }

        return [
            'success' => true,
            'spend_limit' => (int) round($spendLimit),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    private function fetchBusinessManagerAdAccounts(BusinessManager $businessManager): array
    {
        $response = Http::get("https://graph.facebook.com/v21.0/{$businessManager->bm_id}/owned_ad_accounts", [
            'access_token' => $businessManager->access_token,
            'fields' => 'id,name,account_status,currency,balance,spend_cap,timezone_name,funding_source_details,disable_reason',
            'limit' => 500,
        ]);

        if ($response->failed()) {
            $errorMessage = $response->json('error.message')
                ?? $response->json('error_description')
                ?? 'Failed to fetch ad accounts from Facebook.';

            throw new Exception($errorMessage);
        }

        return (array) $response->json('data', []);
    }
}
