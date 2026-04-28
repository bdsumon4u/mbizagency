<?php

namespace App\Filament\Admin\Resources\PaymentMethods\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PaymentMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('processing_fee_percent')
                    ->label('Fee %')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).'%')
                    ->sortable(),
                TextColumn::make('account_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('account_number')
                    ->searchable(),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'Bank' => 'Bank',
                        'MFS' => 'MFS',
                    ])
                    ->searchable(),
                TernaryFilter::make('is_active')
                    ->label('Active'),
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
