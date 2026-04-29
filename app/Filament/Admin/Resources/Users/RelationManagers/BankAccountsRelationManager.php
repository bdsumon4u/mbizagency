<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Filament\Admin\Resources\PaymentMethods\PaymentMethodResource;
use App\Filament\Admin\Resources\PaymentMethods\Tables\PaymentMethodsTable;
use App\Models\PaymentMethod;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class BankAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentMethods';

    protected static ?string $title = 'Bank Accounts';

    public function table(Table $table): Table
    {
        return PaymentMethodsTable::configure($table)
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'account_name', 'account_number'])
                    ->recordTitle(function (PaymentMethod $record): string {
                        if ($record->type === 'MFS') {
                            return $record->name.' ('.$record->account_number.')';
                        }

                        return $record->name.'_'.$record->account_name.' ('.$record->account_number.')';
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (PaymentMethod $record): string => PaymentMethodResource::getUrl('edit', ['record' => $record])),
                DetachAction::make(),
            ]);
    }
}
