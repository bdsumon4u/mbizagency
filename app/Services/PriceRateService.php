<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\PriceRate;
use Illuminate\Support\Collection;
use RuntimeException;

class PriceRateService
{
    /**
     * @return array{bdt_amount: float, dollar_rate: float, source: string, min_usd: float}
     */
    public function convertUsdToBdtForAdAccount(AdAccount $adAccount, float $usdAmount): array
    {
        if ($usdAmount <= 0) {
            throw new RuntimeException('USD amount must be greater than zero.');
        }

        $minimumUsd = $this->getMinimumUsdForAdAccount($adAccount);

        if ($minimumUsd !== null && $usdAmount < $minimumUsd) {
            throw new RuntimeException('Minimum deposit amount is '.number_format($minimumUsd, 2).' USD.');
        }

        $rate = $this->resolveRate(
            $usdAmount,
            $this->getEffectiveRatesForAdAccount($adAccount)->sortByDesc('min_usd')->values()
        );

        if (! $rate) {
            throw new RuntimeException('No price rate configured. Please ask admin to set pricing.');
        }

        $dollarRate = (float) $rate->dollar_rate;

        return [
            'bdt_amount' => $usdAmount * $dollarRate,
            'dollar_rate' => $dollarRate,
            'min_usd' => (float) $rate->min_usd,
            'source' => $rate->ad_account_id ? 'special' : 'global',
        ];
    }

    public function getMinimumUsdForAdAccount(AdAccount $adAccount): ?float
    {
        /** @var PriceRate|null $minimumRate */
        $minimumRate = $this->getEffectiveRatesForAdAccount($adAccount)
            ->sortBy('min_usd')
            ->first();

        return $minimumRate ? (float) $minimumRate->min_usd : null;
    }

    public function getEffectiveRatesForAdAccount(AdAccount $adAccount): Collection
    {
        $bestRatePerTier = PriceRate::query()
            ->where(fn ($query) => $query->where('ad_account_id', $adAccount->id)->orWhereNull('ad_account_id'))
            ->orderBy('min_usd')
            ->orderBy('dollar_rate')
            ->get()
            ->reduce(function (Collection $rates, PriceRate $rate): Collection {
                $tierKey = number_format((float) $rate->min_usd, 2, '.', '');
                /** @var PriceRate|null $choosen */
                $choosen = $rates->get($tierKey);

                if (! $choosen || (float) $rate->dollar_rate < (float) $choosen->dollar_rate) {
                    $rates->put($tierKey, $rate);
                }

                return $rates;
            }, collect())
            ->sortBy(fn (PriceRate $rate): float => (float) $rate->min_usd)
            ->values();

        return $bestRatePerTier->reduce(function (Collection $rates, PriceRate $rate): Collection {
            /** @var PriceRate|null $lastRate */
            $lastRate = $rates->last();

            if (! $lastRate || (float) $rate->dollar_rate < (float) $lastRate->dollar_rate) {
                $rates->push($rate);
            }

            return $rates;
        }, collect());
    }

    /**
     * @return array<int, array{type: string, min_usd: string, dollar_rate: string, min_usd_raw: float, dollar_rate_raw: float}>
     */
    public function getEffectiveRateRowsForAdAccount(AdAccount $adAccount): array
    {
        return $this->getEffectiveRatesForAdAccount($adAccount)
            ->map(fn (PriceRate $rate): array => [
                'type' => $rate->ad_account_id ? 'Special' : 'Global',
                'min_usd' => number_format((float) $rate->min_usd, 2),
                'dollar_rate' => number_format((float) $rate->dollar_rate, 2),
                'min_usd_raw' => (float) $rate->min_usd,
                'dollar_rate_raw' => (float) $rate->dollar_rate,
            ])
            ->values()
            ->all();
    }

    private function resolveRate(float $usdAmount, Collection $rates): ?PriceRate
    {
        $fallback = null;

        foreach ($rates as $rate) {
            $dollarRate = (float) $rate->dollar_rate;

            if ($dollarRate <= 0) {
                continue;
            }

            $fallback ??= $rate;

            if ($usdAmount >= (float) $rate->min_usd) {
                return $rate;
            }
        }

        if (! $fallback) {
            return null;
        }

        return $fallback;
    }
}
