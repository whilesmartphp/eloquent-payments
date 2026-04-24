# Eloquent Payments

Polymorphic, gateway-agnostic payment records for Laravel. Attaches to invoices, expenses, subscriptions, orders, or any payable model.

## Why

`whilesmart/eloquent-invoices` tracks payment state as scalars on the invoice row (`amount_paid_cents`, `paid_at`). That is a good summary view but there is no audit trail: no per-event log, no gateway reference, no multi-installment history, no refund chain, no failed-attempt record.

This package is that audit trail. Invoices, expenses, or anything else payable gets a `payments()` relationship; each `Payment` row is a single event.

## Install

```
composer require whilesmart/eloquent-payments
php artisan migrate
```

Attach the `HasPayments` trait to any payable:

```php
use Whilesmart\Payments\Contracts\Payable;
use Whilesmart\Payments\Traits\HasPayments;

class Invoice extends Model implements Payable
{
    use HasPayments;
}
```

## Data model

Every `Payment` row captures:

- **`payable`** (morph) -- what's being paid for.
- **`owner`** (nullable morph) -- who initiated / owns the payment, typically the workspace.
- **`amount_cents`**, **`currency`**.
- **`status`** -- `pending | authorized | succeeded | failed | refunded | partially_refunded | cancelled`.
- **`direction`** -- `inbound` (collection) / `outbound` (payout, refund).
- **`gateway`**, **`gateway_reference`** -- provider + its ID. Unique pair.
- **`method`** -- free-form string (`card`, `mobile_money`, `bank_transfer`, ...). See `PaymentMethod` enum for canonical values.
- **Audit timestamps** -- `authorized_at`, `succeeded_at`, `failed_at`, `refunded_at`.
- **`failure_reason`** -- text.
- **`parent_payment_id`** -- for refunds, points to the original payment.
- **`metadata`** -- JSON.

## Reflecting onto the payable

The `HasPayments` trait includes `recordPayment()` which creates a Payment AND, when `payments.auto_reflect_on_payable` is on (default), syncs the payable's own summary fields:

```php
$invoice->recordPayment([
    'amount_cents'      => 50000,
    'currency'          => 'USD',
    'status'            => 'succeeded',
    'method'            => 'card',
    'gateway'           => 'stripe',
    'gateway_reference' => 'ch_123...',
    'succeeded_at'      => now(),
]);

// Effect:
//   invoice.amount_paid_cents  += 50000 (or rather, set to sum of succeeded payments)
//   invoice.paid_at             = now() if cumulative >= total_cents
```

If the payable has no `amount_paid_cents` column, reflection is silently skipped -- the package never errors out of ignorance of your schema.

## Routes

Registers an `apiResource` at the configured prefix:

```
GET    /api/payments
POST   /api/payments
GET    /api/payments/{payment}
PUT    /api/payments/{payment}
DELETE /api/payments/{payment}
```

Index filters: `payable_type`, `payable_id`, `owner_type`, `owner_id`, `status`, `direction`, `gateway`, `per_page`.

## Config

`php artisan vendor:publish --tag=payments-config`:

```php
return [
    'register_routes' => env('PAYMENTS_REGISTER_ROUTES', true),
    'route_prefix' => env('PAYMENTS_ROUTE_PREFIX', 'api'),
    'route_middleware' => ['api', 'auth:sanctum'],
    'table' => env('PAYMENTS_TABLE', 'payments'),
    'auto_reflect_on_payable' => env('PAYMENTS_AUTO_REFLECT', true),
];
```

## Siblings

- `whilesmart/eloquent-invoices` -- invoices (money in). Implement `Payable`, attach `HasPayments`.
- `whilesmart/eloquent-expenses` -- expenses (money out). Same pattern; the `Payment` direction flips to `outbound`.
- `whilesmart/eloquent-subscriptions` (future) -- recurring billing.
