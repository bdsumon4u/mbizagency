<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
            'synced_at' => 'datetime',
        ];
    }
}
