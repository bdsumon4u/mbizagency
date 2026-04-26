<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

#[Fillable([
    'user_id',
    'ad_account_id',
    'approved_by_admin_id',
    'type',
    'source',
    'status',
    'amount',
    'note',
    'approved_at',
])]
class Transaction extends Model
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAWAL = 'withdrawal';

    public const SOURCE_USER = 'user';

    public const SOURCE_ADMIN = 'admin';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function approve(Admin $admin): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new RuntimeException('Only pending transactions can be approved.');
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by_admin_id' => $admin->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(Admin $admin): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new RuntimeException('Only pending transactions can be rejected.');
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by_admin_id' => $admin->id,
            'approved_at' => now(),
        ]);
    }
}
