<?php

namespace App\Models;

use App\Casts\FacebookDollar;
use App\Enums\AdAccountDisableReason;
use App\Enums\AdAccountStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_manager_id',
    'user_id',
    'name',
    'act_id',
    'status',
    'currency',
    'balance',
    'amount_spent',
    'prepaid_fund_added',
    'billing_threshold',
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
            'status' => AdAccountStatus::class,
            'spend_cap' => FacebookDollar::class,
            'amount_spent' => FacebookDollar::class,
            'balance' => FacebookDollar::class,
            'prepaid_fund_added' => FacebookDollar::class,
            'billing_threshold' => FacebookDollar::class,
            'disable_reason' => AdAccountDisableReason::class,
            'synced_at' => 'datetime',
        ];
    }

    public function priceRates(): HasMany
    {
        return $this->hasMany(PriceRate::class);
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
