<?php

namespace App\Models;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'status' => WalletTransactionStatus::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'usd_amount' => 'decimal:2',
            'dollar_rate' => 'decimal:2',
            'screenshots' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function getProcessingFeeAttribute(): float
    {
        $paymentMethod = $this->paymentMethod;
        $feePercent = $paymentMethod ? (float) $paymentMethod->processing_fee_percent : 0.0;

        return round(((float) $this->amount) * ($feePercent / 100), 2);
    }

    public function getPayableAmountAttribute(): float
    {
        return ((float) $this->amount) + $this->processing_fee;
    }
}
