<?php

namespace App\Filament\Widgets;

use App\Filament\Actions\DepositFundAction;
use App\Filament\Tables\Columns\AdAccountsTable\AdAccountColumn;
use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Models\AdAccount;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AdAccountsTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(AdAccount::query()->whereBelongsTo(Filament::auth()->user()))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('#')
                    ->rowIndex(),
                AdAccountColumn::make('name')
                    ->label('Ad Account')
                    ->searchable(),
                CurrencyColumn::make('spend_cap')
                    ->label('Limit'),
                CurrencyColumn::make('amount_spent')
                    ->label('Spent'),
                DateTimeColumn::make('synced_at'),
            ])
            ->recordActions([
                DepositFundAction::make()->button(),
            ], RecordActionsPosition::BeforeCells);
    }
}
