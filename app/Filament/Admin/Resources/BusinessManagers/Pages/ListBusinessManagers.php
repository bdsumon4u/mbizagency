<?php

namespace App\Filament\Admin\Resources\BusinessManagers\Pages;

use App\Filament\Admin\Resources\BusinessManagers\BusinessManagerResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListBusinessManagers extends ListRecords
{
    protected static string $resource = BusinessManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver(),
            Action::make('connect_facebook_oauth')
                ->label('Connect Facebook (OAuth)')
                ->icon(Heroicon::OutlinedLink)
                ->url(FacebookOAuthRedirect::getUrl())
                ->openUrlInNewTab(),
        ];
    }
}
