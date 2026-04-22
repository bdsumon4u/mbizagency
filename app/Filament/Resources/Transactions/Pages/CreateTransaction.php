<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $isAdminPanel = Filament::getCurrentPanel()?->getId() === 'admin';

        if (! $isAdminPanel) {
            $data['user_id'] = Filament::auth()->id();
            $data['type'] = Transaction::TYPE_DEPOSIT;
            $data['source'] = Transaction::SOURCE_USER;
            $data['status'] = Transaction::STATUS_PENDING;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $wallet = Wallet::query()->firstOrCreate(
                ['user_id' => $data['user_id']],
                ['balance' => 0]
            );

            $data['wallet_id'] = $wallet->id;
            $isAdminPanel = Filament::getCurrentPanel()?->getId() === 'admin';

            if ($isAdminPanel) {
                $data['source'] = Transaction::SOURCE_ADMIN;
                $data['status'] = Transaction::STATUS_APPROVED;
                $data['approved_by_admin_id'] = Filament::auth()->id();
                $data['approved_at'] = now();
            }

            $transaction = static::getModel()::query()->create($data);

            if ($isAdminPanel) {
                if ($transaction->type === Transaction::TYPE_DEPOSIT) {
                    $wallet->credit((float) $transaction->amount);
                } else {
                    $wallet->debit((float) $transaction->amount);
                }
            }

            return $transaction;
        });
    }
}
