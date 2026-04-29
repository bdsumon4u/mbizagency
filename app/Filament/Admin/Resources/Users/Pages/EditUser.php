<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetPassword')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('success')
                ->schema([
                    TextInput::make('password')
                        ->label('New Password')
                        // ->password()
                        ->default('dollar32')
                        ->required(),
                ])
                ->action(fn (User $record, array $data) => $record->update(['password' => Hash::make($data['password'])]))
                ->successNotificationTitle(fn (array $data) => 'New password: `'.$data['password'].'`')
                ->requiresConfirmation()
                ->modalDescription('Are you sure you want to reset the password?')
                ->modalHeading('Reset Password')
                ->modalSubmitActionLabel('Reset Password')
                ->modalCancelActionLabel('Cancel'),
            DeleteAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'User Info';
    }
}
