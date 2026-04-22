<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BusinessManagers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class BusinessManagerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('bm_id')
                    ->label('BM ID'),
                TextEntry::make('ad_act_prefix')
                    ->label('AD Account Prefix'),
                TextEntry::make('name'),
                TextEntry::make('description'),
                TextEntry::make('status'),
                TextEntry::make('currency'),
                TextEntry::make('balance')
                    ->numeric(),
                TextEntry::make('synced_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
