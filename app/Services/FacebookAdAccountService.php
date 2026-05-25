<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AdAccountDisableReason;
use App\Enums\AdAccountStatus;
use App\Exceptions\FacebookApiException;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class FacebookAdAccountService
{
    private const GRAPH_API_VERSION = 'v25.0';

    private const AD_ACCOUNT_FIELDS = 'id,name,account_status,currency,spend_cap,amount_spent,balance,timezone_name,funding_source_details,disable_reason';

    /**
     * @throws Exception
     */
    public function syncFromBusinessManager(BusinessManager $businessManager): int
    {
        $adAccounts = $this->fetchBusinessManagerAdAccounts($businessManager);
        $syncedCount = 0;

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
                    ...$this->mapAdAccountData($account),
                ],
            );

            $syncedCount++;
        }

        $businessManager->update([
            'synced_at' => now(),
        ]);

        return $syncedCount;
    }

    /**
     * @throws Exception
     */
    public function syncSingleAdAccount(AdAccount $adAccount): void
    {
        $businessManager = $adAccount->businessManager;

        if (! $businessManager instanceof BusinessManager) {
            throw new Exception('Business manager not found for this ad account.');
        }

        $response = Http::get('https://graph.facebook.com/'.self::GRAPH_API_VERSION.'/act_'.$adAccount->act_id, [
            'access_token' => $businessManager->access_token,
            'fields' => self::AD_ACCOUNT_FIELDS,
        ]);

        if ($response->failed()) {
            $this->handleFacebookError($response, $businessManager);
        }

        $details = (array) $response->json();
        info('AdAccount details: ', $details);
        $adAccount->update($this->mapAdAccountData($details));
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    private function fetchBusinessManagerAdAccounts(BusinessManager $businessManager): array
    {
        $response = Http::get('https://graph.facebook.com/'.self::GRAPH_API_VERSION.'/'.$businessManager->bm_id.'/owned_ad_accounts', [
            'access_token' => $businessManager->access_token,
            'fields' => self::AD_ACCOUNT_FIELDS,
            'limit' => 500,
        ]);

        if ($response->failed()) {
            $this->handleFacebookError($response, $businessManager);
        }

        return (array) $response->json('data', []);
    }

    private function handleFacebookError(Response $response, BusinessManager $businessManager): never
    {
        $error = $response->json('error');
        $errorCode = (int) ($error['code'] ?? 0);
        $errorSubcode = (int) ($error['error_subcode'] ?? 0);
        $errorMessage = (string) ($error['message'] ?? $response->json('error_description') ?? 'Facebook API error');

        // Error code 190: Access token has expired or been invalidated
        if ($errorCode === 190) {
            $businessManager->update([
                'status' => BusinessManagerStatus::TOKEN_EXPIRED,
            ]);
        }

        throw new FacebookApiException(
            "Facebook API Error: {$errorMessage}",
            $response,
            $errorCode,
            $errorSubcode
        );
    }

    private function mapAdAccountData(array $account): array
    {
        return [
            'name' => (string) ($account['name'] ?? $account['id'] ?? ''),
            'status' => (int) ($account['account_status'] ?? AdAccountStatus::ACTIVE->value),
            'currency' => (string) ($account['currency'] ?? 'USD'),
            'spend_cap' => (float) ($account['spend_cap'] ?? 0),
            'amount_spent' => (float) ($account['amount_spent'] ?? 0),
            'balance' => (float) ($account['balance'] ?? 0),
            'payment_method' => (string) ($account['funding_source_details']['display_string'] ?? ''),
            'timezone' => (string) ($account['timezone_name'] ?? ''),
            'disable_reason' => AdAccountDisableReason::tryFrom((int) ($account['disable_reason'] ?? 0)),
            'synced_at' => now(),
        ];
    }
}
