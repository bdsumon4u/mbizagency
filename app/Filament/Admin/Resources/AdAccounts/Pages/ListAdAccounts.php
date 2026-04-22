<?php

namespace App\Filament\Admin\Resources\AdAccounts\Pages;

use App\Filament\Admin\Resources\AdAccounts\AdAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdAccounts extends ListRecords
{
    protected static string $resource = AdAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
