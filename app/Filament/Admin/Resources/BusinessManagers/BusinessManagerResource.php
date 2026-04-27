<?php

namespace App\Filament\Admin\Resources\BusinessManagers;

use App\Filament\Admin\Resources\BusinessManagers\Pages\CreateBusinessManager;
use App\Filament\Admin\Resources\BusinessManagers\Pages\EditBusinessManager;
use App\Filament\Admin\Resources\BusinessManagers\Pages\FacebookOAuthRedirect;
use App\Filament\Admin\Resources\BusinessManagers\Pages\ListBusinessManagers;
use App\Filament\Admin\Resources\BusinessManagers\RelationManagers\AdAccountsRelationManager;
use App\Filament\Admin\Resources\BusinessManagers\Schemas\BusinessManagerForm;
use App\Filament\Admin\Resources\BusinessManagers\Tables\BusinessManagersTable;
use App\Models\BusinessManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BusinessManagerResource extends Resource
{
    protected static ?string $model = BusinessManager::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return BusinessManagerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessManagersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AdAccountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinessManagers::route('/'),
            // 'create' => CreateBusinessManager::route('/create'),
            'oauth' => FacebookOAuthRedirect::route('/oauth'),
            'edit' => EditBusinessManager::route('/{record}/edit'),
        ];
    }
}
