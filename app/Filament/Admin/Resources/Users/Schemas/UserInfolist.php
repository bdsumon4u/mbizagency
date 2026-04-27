<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('page_name')
                    ->label('Page Name')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email Address'),
                TextEntry::make('phone_number')
                    ->label('Phone Number')
                    ->placeholder('-'),
            ])
            ->columns(2);
    }
}
