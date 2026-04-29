<?php

namespace App\Filament\Actions;

use App\Actions\SubmitDepositFundOrderAction;
use App\Filament\Actions\DepositFund\DepositFundFormSchema;
use App\Filament\Pages\OrderHistory;
use App\Models\AdAccount;
use App\Models\User;
use App\Services\FacebookAdAccountService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use Throwable;
use Illuminate\Support\HtmlString;

class DepositFundAction
{
    public static function make(): Action
    {
        return Action::make('add_fund')
            ->label('')
            ->tooltip(fn (AdAccount $record): string => 'Add fund to '.$record->name.'.')
            ->icon(new HtmlString('<img src="'.asset('dollar.png').'" alt="Fund" width="36" height="36">'))
            ->color('')
            ->button()
            ->modalWidth(Width::Large)
            ->extraAttributes(['class' => 'add-fund-button'])
            ->visible(fn (AdAccount $record): bool => $record->user instanceof User)
            ->mountUsing(function (?Schema $schema, AdAccount $record, FacebookAdAccountService $facebookAdAccountService): void {
                try {
                    if (! $record->synced_at?->isAfter(now()->subMinutes(5))) {
                        $facebookAdAccountService->syncSingleAdAccount($record);
                    }
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('Could not refresh latest ad account data from Meta.')
                        ->body($exception->getMessage())
                        ->warning()
                        ->send();
                }

                $schema?->fill();
            })
            ->schema(fn (AdAccount $record, DepositFundFormSchema $depositFundFormSchema): array => $depositFundFormSchema->build($record))
            ->action(function (AdAccount $record, array $data, SubmitDepositFundOrderAction $submitDepositFundOrderAction): void {
                try {
                    $result = $submitDepositFundOrderAction($record, $data);

                    Notification::make()
                        ->title($result['approved']
                            ? 'Order approved and spend cap synced successfully.'
                            : 'Order submitted and sent to admins for confirmation.')
                        ->success()
                        ->send();
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->title($exception->getMessage())
                        ->danger()
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('Failed to submit order.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->successRedirectUrl(fn (): string => OrderHistory::getUrl());
    }
}
