<?php

namespace App\Filament\Admin\Resources\PriceRates\Pages;

use App\Filament\Admin\Resources\PriceRates\PriceRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class ListPriceRates extends ListRecords
{
    protected static string $resource = PriceRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(Width::Small)
                ->modalCancelAction(false)
                ->using(function (array $data, string $model): Model {
                    $adAccountIds = $data['ad_account_ids'] ?? [];

                    if (! empty($adAccountIds)) {
                        $firstRate = null;

                        foreach ($adAccountIds as $adAccountId) {
                            $rate = $model::create([
                                'ad_account_id' => $adAccountId,
                                'min_usd' => $data['min_usd'],
                                'dollar_rate' => $data['dollar_rate'],
                            ]);

                            if (! $firstRate) {
                                $firstRate = $rate;
                            }
                        }

                        return $firstRate;
                    }

                    return $model::create([
                        'ad_account_id' => null,
                        'min_usd' => $data['min_usd'],
                        'dollar_rate' => $data['dollar_rate'],
                    ]);
                }),
        ];
    }
}
