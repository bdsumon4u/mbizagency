<?php

namespace App\Filament\Admin\Resources\BusinessManagers\Pages;

use App\Filament\Admin\Resources\BusinessManagers\BusinessManagerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBusinessManager extends EditRecord
{
    protected static string $resource = BusinessManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
