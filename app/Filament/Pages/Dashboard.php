<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('transaction')
                ->label('Transaction')
                ->icon(Heroicon::OutlinedBanknotes)
                ->url(TransactionResource::getUrl('create')),
        ];
    }
}
