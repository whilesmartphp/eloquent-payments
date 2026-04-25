<?php

namespace Whilesmart\Payments\Contracts;

/**
 * Marker interface for models that can appear on the payable side of a payment
 * (Invoice, Expense, Subscription, Order, anything).
 *
 * Any model that additionally exposes `amount_paid_cents` + `total_cents`
 * columns (or equivalent) will have those reflected automatically by the
 * HasPayments trait when a payment succeeds, provided
 * `payments.auto_reflect_on_payable` is on.
 */
interface Payable {}
