<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Filament\Tables\Columns\DateTimeColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('adAccounts'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('page_name')
                    ->label('Company Name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone_number')
                    ->label('Phone Number')
                    ->searchable(),
                TextColumn::make('ad_accounts_count')
                    ->label('Accounts')
                    ->badge()
                    ->sortable(),
                DateTimeColumn::make('email_verified_at')
                    ->sortable(),
                DateTimeColumn::make('created_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                DateTimeColumn::make('updated_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
