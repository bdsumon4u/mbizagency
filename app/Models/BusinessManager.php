<?php

namespace App\Models;

use App\Enums\BusinessManagerStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'bm_id',
    'access_token',
    'ad_act_prefix',
    'name',
    'description',
    'status',
    'currency',
    'balance',
    'synced_at',
])]
class BusinessManager extends Model
{
    protected function casts(): array
    {
        return [
            'status' => BusinessManagerStatus::class,
            'synced_at' => 'datetime',
        ];
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class);
    }
}
