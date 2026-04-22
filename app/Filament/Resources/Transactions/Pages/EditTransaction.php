<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use RuntimeException;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['user_id'], $data['amount'], $data['type']);

        return $data;
    }

    protected function beforeSave(): void
    {
        if (Filament::getCurrentPanel()?->getId() !== 'admin') {
            $this->halt();

            return;
        }

        /** @var Transaction $record */
        $record = $this->getRecord();
        $nextStatus = $this->data['status'] ?? $record->status;

        if ($nextStatus === $record->status) {
            return;
        }

        try {
            if ($nextStatus === Transaction::STATUS_APPROVED) {
                $record->approve(Filament::auth()->user());
            } elseif ($nextStatus === Transaction::STATUS_REJECTED) {
                $record->reject(Filament::auth()->user());
            } else {
                throw new RuntimeException('Status can only be approved or rejected.');
            }
        } catch (RuntimeException $exception) {
            Notification::make()
                ->danger()
                ->title($exception->getMessage())
                ->send();

            $this->halt();
        }
    }
}
