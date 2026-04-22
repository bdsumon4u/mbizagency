<?php

namespace App\Filament\Resources\AdAccounts\Tables;

use App\Enums\AdAccountStatus;
use App\Models\AdAccount;
use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->where('user_id', Filament::auth()->id())
                ->with('businessManager'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('businessManager.name')
                    ->label('Business Manager')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Ad Account')
                    ->searchable(),
                TextColumn::make('act_id')
                    ->label('Ad Account ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (int|string $state): string => AdAccountStatus::tryFrom((int) $state)?->getLabel() ?? (string) $state),
                TextColumn::make('currency'),
                TextColumn::make('balance')
                    ->numeric(),
                TextColumn::make('spend_cap')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('synced_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                Action::make('add_fund')
                    ->label('Add Fund')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalWidth(Width::Large)
                    ->schema(fn (AdAccount $record): array => [
                        Callout::make('Current Balance: '.Filament::auth()->user()->wallet?->balance.' '.$record->currency)
                            ->icon('heroicon-o-banknotes')
                            ->description('This is the current balance of the ad account.')
                            ->success(),
                        Callout::make('Ad Account: '.$record->name.' ('.$record->act_id.')')
                            ->icon('heroicon-o-information-circle')
                            ->description('Current spend cap: '.number_format((float) $record->spend_cap, 2).' '.$record->currency.'. This will be set on Meta as the spend limit for this ad account.')
                            ->info(),
                        TextInput::make('amount')
                            ->label('Amount (BDT)')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function (AdAccount $record, array $data): void {
                        try {
                            DB::transaction(function () use ($record, $data): void {
                                $wallet = Wallet::query()->lockForUpdate()->firstOrCreate(
                                    ['user_id' => Filament::auth()->id()],
                                    ['balance' => 0]
                                );

                                $amount = (float) $data['amount'];
                                $wallet->debit($amount);

                                $record->increment('balance', (int) $amount);
                                $record->update(['synced_at' => now()]);

                                Transaction::query()->create([
                                    'wallet_id' => $wallet->id,
                                    'user_id' => Filament::auth()->id(),
                                    'type' => Transaction::TYPE_WITHDRAWAL,
                                    'source' => Transaction::SOURCE_USER,
                                    'status' => Transaction::STATUS_APPROVED,
                                    'amount' => $amount,
                                    'note' => 'Fund transferred to ad account: '.$record->act_id,
                                    'approved_at' => now(),
                                ]);
                            });

                            Notification::make()
                                ->title('Fund added to ad account successfully.')
                                ->success()
                                ->send();
                        } catch (RuntimeException $exception) {
                            Notification::make()
                                ->title($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
