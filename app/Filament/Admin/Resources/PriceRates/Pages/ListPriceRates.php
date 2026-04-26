<?php

namespace App\Filament\Admin\Resources\PriceRates\Pages;

use App\Filament\Admin\Resources\PriceRates\PriceRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListPriceRates extends ListRecords
{
    protected static string $resource = PriceRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(Width::Small)
                ->createAnother(false),
        ];
    }
}
