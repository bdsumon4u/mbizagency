<?php

namespace App\Filament\Admin\Resources\PriceRates\Tables;

use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PriceRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('adAccount')->orderByRaw('ad_account_id is null desc'))
            ->groups([
                Group::make('adAccount.name')
                    ->label('Ad Account'),
                Group::make('adAccount.user.name')
                    ->label('User Account'),
            ])
            ->defaultGroup('adAccount.user.name')
            ->defaultSort('min_usd', 'asc')
            ->columns([
                TextColumn::make('adAccount.name')
                    ->label('Ad Account')
                    ->placeholder('Regular Rate')
                    ->searchable()
                    ->sortable(),
                CurrencyColumn::make('min_usd')
                    ->label('Min USD')
                    ->searchable()
                    ->sortable(),
                CurrencyColumn::make('dollar_rate', 'BDT')
                    ->label('Dollar Rate')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime(),
                DateTimeColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('ad_account_id')
                    ->label('Ad Account')
                    ->relationship('adAccount', 'name', function ($query) {
                        $query->whereHas('priceRates');
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('bulk_edit')
                        ->label('Bulk Edit')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            TextInput::make('min_usd')
                                ->label('Min USD')
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('dollar_rate')
                                ->label('Dollar Rate (BDT per USD)')
                                ->numeric()
                                ->minValue(1),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $updateData = [];

                            if (isset($data['min_usd']) && $data['min_usd'] !== '') {
                                $updateData['min_usd'] = $data['min_usd'];
                            }

                            if (isset($data['dollar_rate']) && $data['dollar_rate'] !== '') {
                                $updateData['dollar_rate'] = $data['dollar_rate'];
                            }

                            if (! empty($updateData)) {
                                foreach ($records as $record) {
                                    $record->update($updateData);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
