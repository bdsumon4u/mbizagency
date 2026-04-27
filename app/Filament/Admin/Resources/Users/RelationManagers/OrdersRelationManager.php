<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Pages\OrderHistory;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function table(Table $table): Table
    {
        return OrderHistory::configureTable($table);
    }
}
