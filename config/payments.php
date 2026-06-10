<?php

use Whilesmart\Payments\Models\Payment;

return [
    'model' => Payment::class,

    'register_routes' => env('PAYMENTS_REGISTER_ROUTES', true),
    'route_prefix' => env('PAYMENTS_ROUTE_PREFIX', 'api'),
    'route_middleware' => ['api', 'auth:sanctum'],
    'table' => env('PAYMENTS_TABLE', 'payments'),

    // When a successful payment is recorded against a payable that exposes
    // an amount_paid_cents column (e.g. whilesmart/eloquent-invoices), the
    // HasPayments trait will automatically increment that counter and, when
    // cumulative succeeded payments cover total_cents, stamp paid_at.
    'auto_reflect_on_payable' => env('PAYMENTS_AUTO_REFLECT', true),
];
