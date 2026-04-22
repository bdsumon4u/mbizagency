<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

#[Fillable(['user_id', 'balance'])]
class Wallet extends Model
{
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function credit(float $amount): void
    {
        $this->increment('balance', $amount);
        $this->refresh();
    }

    public function debit(float $amount): void
    {
        if ((float) $this->balance < $amount) {
            throw new RuntimeException('Insufficient wallet balance.');
        }

        $this->decrement('balance', $amount);
        $this->refresh();
    }
}
