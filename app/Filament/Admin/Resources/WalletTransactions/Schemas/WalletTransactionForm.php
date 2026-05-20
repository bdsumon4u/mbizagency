<?php

namespace App\Filament\Admin\Resources\WalletTransactions\Schemas;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WalletTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('admin_id')
                    ->relationship('admin', 'name'),
                Select::make('type')
                    ->options(WalletTransactionType::class)
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('balance_after')
                    ->numeric(),
                Select::make('status')
                    ->options(WalletTransactionStatus::class)
                    ->default('pending')
                    ->required(),
                Select::make('payment_method_id')
                    ->relationship('paymentMethod', 'name'),
                Select::make('ad_account_id')
                    ->relationship('adAccount', 'name'),
                TextInput::make('usd_amount')
                    ->numeric(),
                TextInput::make('dollar_rate')
                    ->numeric(),
                Textarea::make('note')
                    ->columnSpanFull(),
                TextInput::make('screenshots'),
                DateTimePicker::make('approved_at'),
            ]);
    }
}
