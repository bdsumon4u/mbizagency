<?php

namespace App\Filament\Admin\Resources\AdAccounts\Tables;

use App\Enums\AdAccountStatus;
use App\Filament\Actions\AssignUserAction;
use App\Filament\Actions\AssignUserBulkAction;
use App\Filament\Actions\DepositFundAction;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Pages\OrderHistory;
use App\Filament\Tables\Columns\AdAccountsTable\AdAccountColumn;
use App\Filament\Tables\Columns\CurrencyColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Models\AdAccount;
use App\Models\User;
use App\Services\FacebookAdAccountService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdAccountsTable
{
    public static function configure(Table $table): Table
    {
        return static::configureWithoutQuery($table)
            ->query(AdAccount::query()
                ->with(['user'])
                ->when(request()->query('highlight'), fn ($query, $id) => $query->orderByRaw('id = ? desc', [$id]))
            );
    }

    public static function configureWithoutQuery(Table $table): Table
    {
        return $table
            ->groups([
                Group::make('user.name')
                    ->label('User')
                    ->getTitleFromRecordUsing(fn (AdAccount $record): string => $record->user ? ($record->user->name.'_'.$record->user->page_name) : 'No User'),
            ])
            ->columns([
                TextColumn::make('#')
                    ->rowIndex()
                    ->alignCenter(),
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
                CurrencyColumn::make('spend_cap')
                    ->label('Limit')
                    ->sortable(),
                CurrencyColumn::make('amount_spent')
                    ->label('Spent')
                    ->sortable(),
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
                SelectFilter::make('user_id')
                    ->label('User')
                    ->multiple()
                    ->options(function (): array {
                        $users = User::query()
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [
                                $user->id => $user->name.'_'.$user->page_name.' ('.$user->email.')',
                            ])
                            ->toArray();

                        return [
                            'assigned' => 'Assigned',
                            'unassigned' => 'Unassigned',
                        ] + $users;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $values = array_filter((array) ($data['values'] ?? []), fn ($v) => $v !== null && $v !== '');

                        if (empty($values)) {
                            return $query;
                        }

                        $hasAssigned = in_array('assigned', $values, true);
                        $hasUnassigned = in_array('unassigned', $values, true);
                        $userIds = array_diff($values, ['assigned', 'unassigned']);

                        if ($hasAssigned && $hasUnassigned && empty($userIds)) {
                            return $query;
                        }

                        return $query->where(function (Builder $query) use ($hasAssigned, $hasUnassigned, $userIds): void {
                            if ($hasAssigned) {
                                $query->orWhereNotNull('user_id');
                            }

                            if ($hasUnassigned) {
                                $query->orWhereNull('user_id');
                            }

                            if (! empty($userIds)) {
                                $query->orWhereIn('user_id', $userIds);
                            }
                        });
                    })
                    ->searchable()
                    ->preload(),
                SelectFilter::make('business_manager_id')
                    ->label('BM')
                    ->relationship('businessManager', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('status')
                    ->options(AdAccountStatus::class)
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('currency')
                    ->options(fn (): array => AdAccount::query()
                        ->select('currency')
                        ->distinct()
                        ->pluck('currency', 'currency')
                        ->toArray())
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('orders')
                    ->label('Orders')
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalContent(fn (AdAccount $record) => view('filament.actions.ad-account-view-orders', [
                        'record' => $record,
                        'table' => 'ad-accounts',
                        'orderHistoryClass' => OrderHistory::class,
                    ]))
                    ->modalHeading('')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->extraAttributes(['class' => 'hidden']),
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
            ], RecordActionsPosition::AfterColumns)
            ->recordAction('orders')
            ->toolbarActions([
                BulkActionGroup::make([
                    AssignUserBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
