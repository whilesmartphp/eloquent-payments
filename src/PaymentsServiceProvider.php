<?php

namespace Whilesmart\Payments;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/payments.php', 'payments');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/payments.php' => config_path('payments.php'),
        ], 'payments-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'payments-migrations');

        if (config('payments.register_routes', true)) {
            Route::middleware(config('payments.route_middleware', ['api', 'auth:sanctum']))
                ->prefix(config('payments.route_prefix', 'api'))
                ->group(__DIR__.'/../routes/api.php');
        }
    }
}
