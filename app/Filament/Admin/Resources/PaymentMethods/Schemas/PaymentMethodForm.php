<?php

namespace App\Filament\Admin\Resources\PaymentMethods\Schemas;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make([
                    Radio::make('type')
                        ->options([
                            'Bank' => 'Bank',
                            'MFS' => 'MFS',
                        ])
                        ->default('Bank')
                        ->required()
                        ->inline(),
                    Toggle::make('is_active')
                        ->default(true)
                        ->inline(false)
                        ->onColor('success')
                        ->offColor('danger')
                        ->required(),
                ])
                    ->columns(2),
                TextInput::make('processing_fee_percent')
                    ->label('Processing Fee (%)')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('name')
                    ->label('Method Name')
                    ->placeholder('bKash Personal')
                    ->required(),
                TextInput::make('account_name')
                    ->maxLength(255)
                    ->hiddenJs('$get(\'type\') === \'MFS\''),
                TextInput::make('account_number')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('branch')
                    ->maxLength(255)
                    ->hiddenJs('$get(\'type\') === \'MFS\''),
                Textarea::make('instructions')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
