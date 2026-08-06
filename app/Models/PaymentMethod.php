<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'logo',
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

    public function getLogoUrl(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return '/storage/'.ltrim($this->logo, '/');
    }

    public function getFormattedNameAttribute(): string
    {
        $logoUrl = $this->getLogoUrl();

        if ($logoUrl) {
            return '<img src="'.e($logoUrl).'" style="display:inline-block; width:20px; height:20px; margin-right:8px; object-fit:contain; vertical-align:middle;" alt="" />'.e($this->name);
        }

        return e($this->name);
    }
}
