<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BusinessManagers\Tables;

use App\Filament\Tables\Columns\BusinessManagerColumn;
use App\Filament\Tables\Columns\DateTimeColumn;
use App\Models\BusinessManager;
use App\Services\FacebookAdAccountService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class BusinessManagersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('adAccounts'))
            ->columns([
                BusinessManagerColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ad_accounts_count')
                    ->label('Accounts')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
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
                //
            ])
            ->recordActions([
                Action::make('sync')
                    ->tooltip('Import Ad Accounts')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('info')
                    ->button()
                    ->action(function (BusinessManager $record): void {
                        try {
                            $syncedCount = app(FacebookAdAccountService::class)->syncFromBusinessManager($record);

                            Notification::make()
                                ->title("Synced {$syncedCount} ad account(s).")
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
