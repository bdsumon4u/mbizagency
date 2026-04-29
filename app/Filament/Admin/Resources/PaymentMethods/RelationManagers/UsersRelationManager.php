<?php

namespace App\Filament\Admin\Resources\PaymentMethods\RelationManagers;

use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return UsersTable::configure($table)
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email', 'page_name'])
                    ->recordTitle(
                        fn (User $record): string => $record->name.'_'.$record->page_name.' ('.$record->email.')',
                    )
                    ->multiple(),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record])),
                DetachAction::make(),
            ]);
    }
}
