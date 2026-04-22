<?php

namespace App\Filament\Admin\Resources\AdAccounts\Tables;

use App\Enums\AdAccountStatus;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Services\FacebookAdAccountService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Support\Enums\Width;
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
                Action::make('limit')
                    ->icon('heroicon-o-currency-dollar')
                    ->modalWidth(Width::Large)
                    ->modalHeading('Set Spend Cap on Meta')
                    ->schema(function (AdAccount $record): array {
                        return [
                            Callout::make('Ad Account: '.$record->name.' ('.$record->act_id.')')
                                ->icon('heroicon-o-information-circle')
                                ->description('Current spend cap: '.number_format((float) $record->spend_cap, 2).' '.$record->currency.'. This will be set on Meta as the spend limit for this ad account.')
                                ->info(),
                            TextInput::make('spend_limit')
                                ->label('Spend Cap')
                                ->numeric()
                                ->minValue(0.01)
                                ->step(0.01)
                                ->required(),
                        ];
                    })
                    ->action(function (AdAccount $record, array $data): void {
                        $businessManager = $record->businessManager;

                        if (! $businessManager instanceof BusinessManager) {
                            Notification::make()
                                ->title('Business manager not found for this ad account.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $service = app(FacebookAdAccountService::class);
                        $validation = $service->validateSpendLimit((float) $data['spend_limit'], (string) $record->currency);

                        if (! $validation['valid']) {
                            Notification::make()
                                ->title('Invalid spend cap')
                                ->body(implode("\n", $validation['errors']))
                                ->danger()
                                ->send();

                            return;
                        }

                        $response = $service->setSpendLimit($businessManager, (string) $record->act_id, (float) $data['spend_limit']);

                        if (! ($response['success'] ?? false)) {
                            Notification::make()
                                ->title('Failed to update spend cap')
                                ->body((string) ($response['message'] ?? 'Unknown error'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'spend_cap' => $response['spend_limit'],
                            'synced_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Spend cap updated')
                            ->body('Spend cap set to '.number_format((float) ($response['spend_limit'] ?? $data['spend_limit']), 2).' '.$record->currency)
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
