<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Models\Transaction;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        $isAdminPanel = Filament::getCurrentPanel()?->getId() === 'admin';

        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->options(fn () => User::query()->pluck('email', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible($isAdminPanel),
                Select::make('type')
                    ->options([
                        Transaction::TYPE_DEPOSIT => 'Deposit',
                        Transaction::TYPE_WITHDRAWAL => 'Withdrawal',
                    ])
                    ->required()
                    ->default(Transaction::TYPE_DEPOSIT)
                    ->disabled(! $isAdminPanel),
                TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->step(0.01),
                Textarea::make('note')
                    ->maxLength(500)
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        Transaction::STATUS_PENDING => 'Pending',
                        Transaction::STATUS_APPROVED => 'Approved',
                        Transaction::STATUS_REJECTED => 'Rejected',
                    ])
                    ->visible($isAdminPanel)
                    ->disabled(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
