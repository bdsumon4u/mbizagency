<?php

namespace App\Filament\Admin\Resources\IncomingSms\Pages;

use App\Filament\Admin\Resources\IncomingSms\IncomingSmsResource;
use Filament\Resources\Pages\ListRecords;

class ListIncomingSms extends ListRecords
{
    protected static string $resource = IncomingSmsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
