<?php

namespace App\Filament\Admin\Resources\BusinessManagers\RelationManagers;

use App\Filament\Admin\Resources\AdAccounts\Tables\AdAccountsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class AdAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'adAccounts';

    protected static ?string $title = 'Ad Accounts';

    public function table(Table $table): Table
    {
        return AdAccountsTable::configure($table);
    }
}
