<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AdAccount;
use App\Services\FacebookAdAccountService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncAdAccountDataJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $adAccountId) {}

    public function backoff(): array
    {
        return [100, 500, 1000];
    }

    public function handle(FacebookAdAccountService $facebookAdAccountService): void
    {
        $adAccount = AdAccount::query()->find($this->adAccountId);

        if (! $adAccount instanceof AdAccount || $adAccount->synced_at?->isAfter(now()->subMinutes(15))) {
            return;
        }

        $facebookAdAccountService->syncSingleAdAccount($adAccount);
    }
}
