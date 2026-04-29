<?php

namespace App\Filament\Actions;

use App\Models\AdAccount;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class AssignUserAction
{
    public static function make(): Action
    {
        return Action::make('assign_user')
            ->label('Assign User')
            ->tooltip(fn (AdAccount $record): string => 'Assign user to '.$record->name.'.')
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
            });
    }
}
