<?php

namespace App\Filament\Admin\Resources\AdAccounts;

use App\Filament\Admin\Resources\AdAccounts\Pages\CreateAdAccount;
use App\Filament\Admin\Resources\AdAccounts\Pages\EditAdAccount;
use App\Filament\Admin\Resources\AdAccounts\Pages\ListAdAccounts;
use App\Filament\Admin\Resources\AdAccounts\Schemas\AdAccountForm;
use App\Filament\Admin\Resources\AdAccounts\Tables\AdAccountsTable;
use App\Models\AdAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdAccountResource extends Resource
{
    protected static ?string $model = AdAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AdAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdAccounts::route('/'),
            'create' => CreateAdAccount::route('/create'),
            // 'edit' => EditAdAccount::route('/{record}/edit'),
        ];
    }
}
