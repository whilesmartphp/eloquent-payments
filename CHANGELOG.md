# Changelog

All notable changes to `whilesmart/eloquent-payments` are documented here.

## [1.0.0] - 2026-04-24

- Initial release
- `Payment` model with polymorphic `payable`, `owner`, and `account` relations
- `HasPayments` trait + `Payable` contract for any payable model (invoices, expenses, subscriptions, orders)
- `recordPayment()` helper with automatic reflection onto `amount_paid_cents` / `paid_at` on the payable when those columns exist
- Reflection is a silent no-op when the payable has no `amount_paid_cents` column
- `PaymentStatus`, `PaymentDirection`, `PaymentMethod` enums
- Refund chain via `parent_payment_id` self-reference
- Unique `(gateway, gateway_reference)` constraint
- Auto-registered API routes: `apiResource payments`
- Config flag `auto_reflect_on_payable` to disable automatic reflection
- Swappable `Payment` model via the `payments.model` config
- Factory for testing
- Publishable config (`payments-config`) and migrations (`payments-migrations`)
