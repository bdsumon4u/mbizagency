<?php

namespace App\Models;

use App\Enums\IncomingSmsStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IncomingSms extends Model
{
    protected $table = 'incoming_sms';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'fee' => 'decimal:2',
            'transaction_time' => 'datetime',
            'matched_at' => 'datetime',
            'status' => IncomingSmsStatus::class,
            'metadata' => 'array',
        ];
    }

    public function matched(): MorphTo
    {
        return $this->morphTo();
    }

    public function paymentVerifications(): HasMany
    {
        return $this->hasMany(PaymentVerification::class);
    }

    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->where('status', IncomingSmsStatus::Unmatched);
    }
}
