<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'type',
    'processing_fee_percent',
    'account_name',
    'account_number',
    'branch',
    'instructions',
    'is_active',
])]
class PaymentMethod extends Model
{
    protected function casts(): array
    {
        return [
            'processing_fee_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
