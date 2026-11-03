<?php

namespace App\Filament\Admin\Resources\IncomingSms;

use App\Filament\Admin\Resources\IncomingSms\Pages\ListIncomingSms;
use App\Filament\Admin\Resources\IncomingSms\Tables\IncomingSmsTable;
use App\Models\IncomingSms;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IncomingSmsResource extends Resource
{
    protected static ?string $model = IncomingSms::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $navigationLabel = 'SMS Logs';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return 'Finance';
    }

    public static function table(Table $table): Table
    {
        return IncomingSmsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncomingSms::route('/'),
        ];
    }
}
