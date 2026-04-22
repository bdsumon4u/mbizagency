<?php

namespace App\Filament\Admin\Resources\AdAccounts\Pages;

use App\Filament\Admin\Resources\AdAccounts\AdAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdAccount extends EditRecord
{
    protected static string $resource = AdAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
