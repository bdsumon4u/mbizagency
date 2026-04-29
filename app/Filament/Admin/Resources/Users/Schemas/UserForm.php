<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('page_name')
                    ->label('Company Name')
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone_number')
                    ->label('Phone Number')
                    ->maxLength(30),
                TextInput::make('password')
                    ->label('Password')
                    ->revealable()
                    ->copyable()
                    ->password()
                    ->required()
                    ->default(fn (Set $set) => $set('password', Str::random(10)))
                    ->prefixAction(
                        Action::make('generate')
                            ->action(fn (Set $set) => $set('password', Str::random(10)))
                            ->icon('heroicon-o-arrow-path')
                    )
                    ->columnSpanFull()
                    ->visibleOn(Operation::Create),
            ]);
    }
}
