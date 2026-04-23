<?php

namespace App\Filament\Admin\Resources\AdAccounts\Tables;

use App\Enums\AdAccountStatus;
use App\Filament\Actions\DepositFundAction;
use App\Models\AdAccount;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('businessManager.name')
                    ->label('Business Manager')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('Ad Account')
                    ->searchable(),
                TextColumn::make('act_id')
                    ->label('Ad Account ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (int|string $state): string => AdAccountStatus::tryFrom((int) $state)?->getLabel() ?? (string) $state)
                    ->searchable(),
                TextColumn::make('currency')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('balance')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_method')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('spend_cap')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('account_type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('synced_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(AdAccountStatus::getOptions())
                    ->searchable(),
                SelectFilter::make('currency')
                    ->options(fn (): array => AdAccount::query()
                        ->select('currency')
                        ->distinct()
                        ->pluck('currency', 'currency')
                        ->toArray())
                    ->searchable(),
                SelectFilter::make('business_manager_id')
                    ->label('Business Manager')
                    ->relationship('businessManager', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DepositFundAction::make(),
                Action::make('assign_user')
                    ->label('Assign User')
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Select::make('user_id')
                            ->label('User')
                            ->options(fn (): array => User::query()->pluck('email', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->fillForm(fn (AdAccount $record): array => [
                        'user_id' => $record->user_id,
                    ])
                    ->action(function (AdAccount $record, array $data): void {
                        $record->update([
                            'user_id' => $data['user_id'],
                        ]);

                        Notification::make()
                            ->title('Ad account assigned successfully.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
