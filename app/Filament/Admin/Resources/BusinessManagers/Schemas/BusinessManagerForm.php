<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BusinessManagers\Schemas;

use App\Enums\BusinessManagerStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class BusinessManagerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bm_id')
                    ->label('BM ID')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('ad_act_prefix')
                    ->label('AD Account Prefix'),
                Textarea::make('access_token')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(BusinessManagerStatus::class)
                    ->default(BusinessManagerStatus::ACTIVE->value)
                    ->searchable()
                    ->required(),
            ])
            ->columns(3);
    }
}
