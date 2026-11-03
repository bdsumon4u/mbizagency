<?php

namespace App\Models;

use App\Enums\PaymentVerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentVerification extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'confidence_score' => 'decimal:2',
            'is_suspicious' => 'boolean',
            'transaction_time' => 'datetime',
            'checked_at' => 'datetime',
            'verified_at' => 'datetime',
            'verification_status' => PaymentVerificationStatus::class,
            'ai_response' => 'array',
        ];
    }

    public function verifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function incomingSms(): BelongsTo
    {
        return $this->belongsTo(IncomingSms::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('verification_status', PaymentVerificationStatus::Pending);
    }
}
