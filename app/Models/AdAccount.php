<?php

namespace App\Models;

use App\Enums\AdAccountDisableReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_manager_id',
    'user_id',
    'name',
    'act_id',
    'status',
    'currency',
    'balance',
    'payment_method',
    'spend_cap',
    'timezone',
    'account_type',
    'description',
    'disable_reason',
    'synced_at',
])]
class AdAccount extends Model
{
    protected function casts(): array
    {
        return [
            'disable_reason' => AdAccountDisableReason::class,
            'synced_at' => 'datetime',
        ];
    }

    public function businessManager(): BelongsTo
    {
        return $this->belongsTo(BusinessManager::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
