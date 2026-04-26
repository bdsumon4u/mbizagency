<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BusinessManagerStatus;
use App\Models\BusinessManager;
use Exception;
use Illuminate\Support\Facades\Http;

final readonly class FacebookBusinessManagerSyncService
{
    private const GRAPH_API_VERSION = 'v25.0';

    public function sync(string $accessToken, ?int $userId = null): int
    {
        $businessManagers = $this->fetchAccessibleBusinessManagers($accessToken);

        $upsertedCount = 0;

        foreach ($businessManagers as $businessManager) {
            $bmId = (string) ($businessManager['id'] ?? '');

            if ($bmId === '') {
                continue;
            }

            BusinessManager::query()->updateOrCreate(
                ['bm_id' => $bmId],
                [
                    'user_id' => $userId,
                    'name' => (string) ($businessManager['name'] ?? $bmId),
                    'description' => (string) ($businessManager['about'] ?? ''),
                    'access_token' => $accessToken,
                    'status' => BusinessManagerStatus::ACTIVE->value,
                    'synced_at' => now(),
                ],
            );

            $upsertedCount++;
        }

        return $upsertedCount;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    private function fetchAccessibleBusinessManagers(string $accessToken): array
    {
        $response = Http::get('https://graph.facebook.com/'.self::GRAPH_API_VERSION.'/me/businesses', [
            'access_token' => $accessToken,
            'fields' => 'id,name,about',
            'limit' => 200,
        ]);

        if ($response->failed()) {
            $errorMessage = $response->json('error.message')
                ?? $response->json('error_description')
                ?? 'Failed to fetch business managers from Facebook.';

            throw new Exception($errorMessage);
        }

        return (array) $response->json('data', []);
    }
}
