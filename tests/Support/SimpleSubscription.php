<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Payments\Contracts\Payable;
use Whilesmart\Payments\Traits\HasPayments;

/**
 * A Payable with NO amount_paid_cents / total_cents columns.
 * Used to prove that reflection is a silent no-op on payables that don't
 * carry summary columns.
 */
class SimpleSubscription extends Model implements Payable
{
    use HasPayments;

    protected $table = 'subscriptions';

    protected $guarded = [];
}
