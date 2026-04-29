<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected function casts(): array
    {
        return [
            'usd_amount' => 'decimal:2',
            'bdt_amount' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'dollar_rate' => 'decimal:2',
            'spend_cap' => 'integer',
            'source' => OrderSource::class,
            'status' => OrderStatus::class,
            'screenshots' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }
}
