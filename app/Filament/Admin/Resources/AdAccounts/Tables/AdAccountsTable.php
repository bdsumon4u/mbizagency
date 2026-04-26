<?php

namespace App\Filament\Admin\Resources\AdAccounts\Tables;

use App\Enums\AdAccountStatus;
use App\Filament\Actions\AssignUserAction;
use App\Filament\Actions\AssignUserBulkAction;
use App\Filament\Actions\DepositFundAction;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Tables\Columns\AdAccountColumn;
use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Models\AdAccount;
use App\Services\FacebookAdAccountService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class AdAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('businessManager.name')
                    ->label('BM')
                    ->sortable()
                    ->description(fn (AdAccount $record): string => $record->businessManager?->bm_id)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->url(function (AdAccount $record): ?string {
                        if (! $record->user_id) {
                            return null;
                        }

                        return UserResource::getUrl('view', ['record' => $record->user_id]);
                    }),
                AdAccountColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                CurrencyColumn::make('spend_cap')
                    ->label('Spend Limit')
                    ->sortable(),
                CurrencyColumn::make('amount_spent')
                    ->label('Spent')
                    ->sortable(),
                TextColumn::make('remaining')
                    ->label('Remaining')
                    ->sortable()
                    ->getStateUsing(function (AdAccount $record): string {
                        return Number::currency($record->spend_cap - $record->amount_spent, $record->currency);
                    }),
                CurrencyColumn::make('balance')
                    ->label('Due')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                CurrencyColumn::make('prepaid_fund_added')
                    ->label('Fund')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                CurrencyColumn::make('billing_threshold')
                    ->label('Threshold')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('account_type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                DateTimeColumn::make('synced_at')
                    ->sortable(),
                DateTimeColumn::make('created_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                DateTimeColumn::make('updated_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('business_manager_id')
                    ->label('BM')
                    ->relationship('businessManager', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(AdAccountStatus::class)
                    ->searchable(),
                SelectFilter::make('currency')
                    ->options(fn (): array => AdAccount::query()
                        ->select('currency')
                        ->distinct()
                        ->pluck('currency', 'currency')
                        ->toArray())
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('sync')
                    ->tooltip(fn (AdAccount $record): string => 'Sync '.$record->name.'.')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('info')
                    ->button()
                    ->action(function (AdAccount $record): void {
                        try {
                            app(FacebookAdAccountService::class)->syncSingleAdAccount($record);

                            Notification::make()
                                ->title('Ad account synced successfully.')
                                ->success()
                                ->send();
                        } catch (Exception $exception) {
                            Notification::make()
                                ->title('Ad account sync failed')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                DepositFundAction::make(),
                AssignUserAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    AssignUserBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
