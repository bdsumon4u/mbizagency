<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncAdAccountDataJob;
use App\Models\AdAccount;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-ad-accounts-data')]
#[Description('Sync ad account data from Facebook/Meta.')]
final class SyncAdAccountsData extends Command
{
    public function handle(): int
    {
        $dispatchedCount = 0;

        AdAccount::query()
            ->whereNotNull('business_manager_id')
            ->whereNotNull('user_id')
            ->where(fn ($query) => $query->whereNull('synced_at')->orWhere('synced_at', '<', now()->subMinutes(15)))
            ->chunkById(100, function ($adAccounts) use (&$dispatchedCount): void {
                foreach ($adAccounts as $adAccount) {
                    SyncAdAccountDataJob::dispatch($adAccount->id);
                    $dispatchedCount++;
                }
            });

        $this->info("Dispatched {$dispatchedCount} ad account sync jobs.");

        return self::SUCCESS;
    }
}
