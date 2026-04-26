<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Tables\Columns\DateTimeColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('approvedByAdmin'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('amount')
                    ->money('BDT'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('approvedByAdmin.email')
                    ->label('Approved By')
                    ->toggleable(isToggledHiddenByDefault: true),
                DateTimeColumn::make('created_at')
                    ->sortable(),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([]);
    }
}
