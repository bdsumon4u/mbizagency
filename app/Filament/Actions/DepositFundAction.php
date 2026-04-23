<?php

namespace App\Filament\Actions;

use App\Models\AdAccount;
use App\Models\Admin;
use App\Models\BusinessManager;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FacebookAdAccountService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DepositFundAction
{
    public static function make(): Action
    {
        return Action::make('add_fund')
            ->label('Add Fund')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->modalWidth(Width::Large)
            ->visible(fn (AdAccount $record): bool => $record->user instanceof User)
            ->schema(fn (AdAccount $record): array => [
                Callout::make('Current Wallet Balance: '.number_format((float) self::resolveWalletBalance($record), 2).' BDT')
                    ->icon('heroicon-o-banknotes')
                    ->description('This amount will be debited from wallet and added to this ad account.')
                    ->success(),
                Callout::make('Ad Account: '.$record->name.' ('.$record->act_id.')')
                    ->icon('heroicon-o-information-circle')
                    ->description('Current ad account balance: '.number_format((float) $record->balance, 2).' '.$record->currency)
                    ->info(),
                TextInput::make('amount')
                    ->label('Amount (BDT)')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required(),
            ])
            ->action(function (AdAccount $record, array $data): void {
                if (! $record->user) {
                    Notification::make()
                        ->title('Please assign a user to this ad account first.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    DB::transaction(function () use ($record, $data): void {
                        $wallet = Wallet::query()->lockForUpdate()->firstOrCreate(
                            ['user_id' => $record->user->id],
                            ['balance' => 0]
                        );

                        $amount = (float) $data['amount'];
                        $wallet->debit($amount);

                        $record->increment('balance', (int) $amount);
                        $record->update(['synced_at' => now()]);

                        $admin = self::whichAdmin();

                        Transaction::query()->create([
                            'wallet_id' => $wallet->id,
                            'user_id' => $record->user->id,
                            'approved_by_admin_id' => $admin?->id,
                            'type' => Transaction::TYPE_WITHDRAWAL,
                            'source' => $admin ? Transaction::SOURCE_ADMIN : Transaction::SOURCE_USER,
                            'status' => Transaction::STATUS_APPROVED,
                            'amount' => $amount,
                            'note' => 'Fund transferred to ad account: '.$record->act_id,
                            'approved_at' => now(),
                        ]);
                    });

                    $record->refresh();
                    self::syncSpendCapForAdmin($record);

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
            });
    }

    private static function resolveWalletBalance(AdAccount $record): float
    {
        if (! $record->user instanceof User) {
            return 0;
        }

        return (float) (Wallet::query()->where('user_id', $record->user->id)->value('balance') ?? 0);
    }

    private static function whichAdmin(): ?Admin
    {
        if (Filament::getCurrentPanel()?->getAuthGuard() !== 'admin') {
            return null;
        }

        $admin = Filament::auth()->user();

        return $admin instanceof Admin ? $admin : null;
    }

    private static function syncSpendCapForAdmin(AdAccount $record): void
    {
        $admin = self::whichAdmin();
        if (! $admin instanceof Admin) {
            return;
        }

        $businessManager = $record->businessManager;
        if (! $businessManager instanceof BusinessManager) {
            return;
        }

        $service = app(FacebookAdAccountService::class);
        $targetSpendLimit = (float) $record->balance;
        $validation = $service->validateSpendLimit($targetSpendLimit, (string) $record->currency);

        if (! $validation['valid']) {
            Notification::make()
                ->title('Fund added, but spend cap validation failed.')
                ->body(implode("\n", $validation['errors']))
                ->warning()
                ->send();

            return;
        }

        $response = $service->setSpendLimit(
            $businessManager,
            (string) $record->act_id,
            $targetSpendLimit,
        );

        if (! ($response['success'] ?? false)) {
            Notification::make()
                ->title('Fund added, but failed to update spend cap on Meta.')
                ->body((string) ($response['message'] ?? 'Unknown error'))
                ->warning()
                ->send();

            return;
        }

        $record->update([
            'spend_cap' => $response['spend_limit'] ?? (int) $record->balance,
            'synced_at' => now(),
        ]);
    }
}
