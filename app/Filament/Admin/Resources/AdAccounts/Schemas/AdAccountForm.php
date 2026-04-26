<?php

namespace App\Filament\Admin\Resources\AdAccounts\Schemas;

use App\Enums\AdAccountDisableReason;
use App\Enums\AdAccountStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AdAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('business_manager_id')
                    ->label('Business Manager')
                    ->relationship('businessManager', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('user_id')
                    ->relationship('user', 'email')
                    ->searchable(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('act_id')
                    ->label('Ad Account ID')
                    ->required(),
                Select::make('status')
                    ->options(AdAccountStatus::class)
                    ->required()
                    ->searchable()
                    ->default(AdAccountStatus::ACTIVE->value),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                TextInput::make('balance')
                    ->label('Outstanding Due')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('amount_spent')
                    ->label('Spent')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('prepaid_fund_added')
                    ->label('Fund Added')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('billing_threshold')
                    ->label('Threshold')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('payment_method'),
                TextInput::make('timezone'),
                TextInput::make('account_type'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('disable_reason')
                    ->options(AdAccountDisableReason::class)
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('synced_at'),
            ]);
    }
}
