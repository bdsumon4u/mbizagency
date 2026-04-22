<?php

namespace App\Filament\Resources\AdAccounts;

use App\Filament\Resources\AdAccounts\Pages\ListAdAccounts;
use App\Filament\Resources\AdAccounts\Tables\AdAccountsTable;
use App\Models\AdAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdAccountResource extends Resource
{
    protected static ?string $model = AdAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'Ads';
    }

    public static function table(Table $table): Table
    {
        return AdAccountsTable::configure($table);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdAccounts::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
