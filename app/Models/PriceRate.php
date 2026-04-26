<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['ad_account_id', 'min_usd', 'dollar_rate'])]
class PriceRate extends Model
{
    protected function casts(): array
    {
        return [
            'min_usd' => 'decimal:2',
            'dollar_rate' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PriceRate $priceRate): void {
            $exists = self::query()
                ->where('min_usd', $priceRate->min_usd)
                ->where(function ($query) use ($priceRate): void {
                    if ($priceRate->ad_account_id === null) {
                        $query->whereNull('ad_account_id');

                        return;
                    }

                    $query->where('ad_account_id', $priceRate->ad_account_id);
                })
                ->when($priceRate->exists, fn ($query) => $query->whereKeyNot($priceRate->id))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'min_usd' => 'Min USD must be unique for global or per-user scope.',
                ]);
            }
        });
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }
}
