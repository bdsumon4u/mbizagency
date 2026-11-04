<?php

namespace App\Filament\Admin\Resources\IncomingSms\Pages;

use App\Filament\Admin\Resources\IncomingSms\IncomingSmsResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class ListIncomingSms extends ListRecords
{
    protected static string $resource = IncomingSmsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runVerification')
                ->label('Run Verification Check')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->action(function () {
                    Artisan::call('payment-verification:process');

                    Notification::make()
                        ->title('Verification engine executed successfully.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
