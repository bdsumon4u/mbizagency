<?php

namespace App\Filament\Actions;

use App\Models\AdAccount;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class AssignUserBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('assign_user_bulk')
            ->label('Assign User')
            ->icon(Heroicon::OutlinedUserPlus)
            ->modalWidth(Width::Large)
            ->schema([
                Select::make('user_id')
                    ->label('Select User')
                    ->relationship('user', 'email')
                    ->getOptionLabelFromRecordUsing(function (User $record): string {
                        return $record->name.'_'.$record->page_name.' ('.$record->email.')';
                    })
                    ->searchable(['name', 'page_name', 'email'])
                    ->preload()
                    ->required(),
            ])
            ->action(function (Collection $records, array $data): void {
                $updatedCount = AdAccount::query()
                    ->whereKey($records->pluck('id'))
                    ->update([
                        'user_id' => $data['user_id'],
                    ]);

                Notification::make()
                    ->title("Assigned user to {$updatedCount} ad account(s).")
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
