<?php

namespace App\Filament\Widgets;

use App\Filament\Actions\DepositFundAction;
use App\Filament\Tables\Columns\AdAccountColumn;
use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Models\AdAccount;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Number;

class AdAccountsTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(AdAccount::query()->whereBelongsTo(Filament::auth()->user()))
            ->defaultSort('id', 'desc')
            ->columns([
                AdAccountColumn::make('name')
                    ->label('Ad Account')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                CurrencyColumn::make('spend_cap')
                    ->label('Spend Limit'),
                CurrencyColumn::make('amount_spent')
                    ->label('Spent'),
                TextColumn::make('remaining')
                    ->label('Remaining')
                    ->getStateUsing(function (AdAccount $record): string {
                        return Number::currency($record->spend_cap - $record->amount_spent, $record->currency);
                    }),
                DateTimeColumn::make('synced_at'),
            ])
            ->recordActions([
                DepositFundAction::make()->button(),
            ]);
    }
}
