<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Payments\Contracts\Payable;
use Whilesmart\Payments\Traits\HasPayments;

class TestInvoice extends Model implements Payable
{
    use HasPayments;

    protected $table = 'invoices';

    protected $guarded = [];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    protected $fillable = ['number', 'total_cents', 'amount_paid_cents', 'paid_at'];
}
