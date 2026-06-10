<?php

namespace Whilesmart\Payments\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;
use Whilesmart\Payments\Enums\PaymentDirection;
use Whilesmart\Payments\Enums\PaymentStatus;
use Whilesmart\Payments\Models\Payment;

/**
 * Add to any model that can be paid for (Invoice, Expense, Subscription, ...).
 *
 * The `recordPayment()` helper is the preferred way to register a payment: it
 * creates the Payment row and, if the payable exposes `amount_paid_cents`
 * (+ optionally `total_cents` / `paid_at`), reflects the succeeded-total back
 * onto the payable so existing summary columns stay in sync.
 */
trait HasPayments
{
    public function payments(): MorphMany
    {
        return $this->morphMany(config('payments.model', Payment::class), 'payable');
    }

    public function succeededPaymentsSumCents(): int
    {
        return (int) $this->payments()
            ->where('status', PaymentStatus::Succeeded->value)
            ->where('direction', PaymentDirection::Inbound->value)
            ->sum('amount_cents');
    }

    public function recordPayment(array $attributes): Payment
    {
        $payment = $this->payments()->create($attributes);

        if (config('payments.auto_reflect_on_payable', true)) {
            $this->reflectPaymentTotal();
        }

        return $payment;
    }

    public function reflectPaymentTotal(): void
    {
        $table = $this->getTable();

        if (! Schema::hasColumn($table, 'amount_paid_cents')) {
            return;
        }

        $paid = $this->succeededPaymentsSumCents();
        $this->amount_paid_cents = $paid;

        // If the payable also carries a total + paid_at, flip paid_at when covered.
        if (Schema::hasColumn($table, 'total_cents')
            && Schema::hasColumn($table, 'paid_at')
            && (int) $this->total_cents > 0
            && $paid >= (int) $this->total_cents
            && empty($this->paid_at)) {
            $this->paid_at = now();
        }

        $this->save();
    }
}
