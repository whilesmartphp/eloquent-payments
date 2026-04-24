<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Payable WITH amount_paid_cents / total_cents / paid_at columns
        // (matches the real eloquent-invoices shape).
        \Illuminate\Support\Facades\Schema::create('invoices', function ($table) {
            $table->id();
            $table->string('number');
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->unsignedBigInteger('amount_paid_cents')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Payable WITHOUT those columns. Proves the reflection code path is
        // a silent no-op when the payable has no summary fields.
        \Illuminate\Support\Facades\Schema::create('subscriptions', function ($table) {
            $table->id();
            $table->string('plan');
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            \Whilesmart\Payments\PaymentsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Routes on; strip auth middleware for package tests.
        $app['config']->set('payments.route_middleware', ['api']);
    }
}
