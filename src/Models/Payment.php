<?php

namespace Whilesmart\Payments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Whilesmart\Payments\Database\Factories\PaymentFactory;
use Whilesmart\Payments\Enums\PaymentDirection;
use Whilesmart\Payments\Enums\PaymentStatus;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => PaymentStatus::class,
        'direction' => PaymentDirection::class,
        'authorized_at' => 'datetime',
        'succeeded_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('payments.table', 'payments');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The account money moved in or out of (bank, wallet, cash register,
     * card processor). Optional -- typically a whilesmart/eloquent-accounts
     * Account, but any model works.
     */
    public function account(): MorphTo
    {
        return $this->morphTo();
    }

    /** Original payment this refund points to, if any. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_payment_id');
    }

    /** Refunds against this payment. */
    public function refunds(): HasMany
    {
        return $this->hasMany(self::class, 'parent_payment_id');
    }

    public function isSucceeded(): bool
    {
        return $this->status === PaymentStatus::Succeeded;
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
