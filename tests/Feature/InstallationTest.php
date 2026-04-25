<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    #[Test]
    public function migration_creates_the_payments_table(): void
    {
        $this->assertTrue(Schema::hasTable('payments'));

        foreach ([
            'payable_type', 'payable_id', 'owner_type', 'owner_id', 'account_type', 'account_id',
            'amount_cents', 'currency', 'status', 'direction',
            'gateway', 'gateway_reference', 'method',
            'authorized_at', 'succeeded_at', 'failed_at', 'refunded_at',
            'parent_payment_id', 'metadata', 'deleted_at',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('payments', $column),
                "payments.{$column} missing -- consuming apps will break."
            );
        }
    }

    #[Test]
    public function config_defaults_are_loaded_from_the_package(): void
    {
        $this->assertSame('payments', config('payments.table'));
        $this->assertSame('api', config('payments.route_prefix'));
        $this->assertTrue(config('payments.auto_reflect_on_payable'));
    }

    #[Test]
    public function api_resource_routes_are_registered(): void
    {
        $registered = collect(Route::getRoutes())->map(fn ($r) => $r->uri())->all();

        $this->assertContains('api/payments', $registered);
        $this->assertContains('api/payments/{payment}', $registered);
    }

    #[Test]
    public function publishable_tags_are_registered(): void
    {
        $configTag = ServiceProvider::$publishGroups['payments-config'] ?? null;
        $migrationsTag = ServiceProvider::$publishGroups['payments-migrations'] ?? null;

        $this->assertNotNull($configTag, 'Missing payments-config publish tag.');
        $this->assertNotNull($migrationsTag, 'Missing payments-migrations publish tag.');
    }

    #[Test]
    public function route_middleware_can_be_overridden_via_config(): void
    {
        $route = collect(Route::getRoutes())->first(fn ($r) => str_starts_with($r->uri(), 'api/payments'));

        $this->assertNotNull($route);
        $this->assertContains('api', $route->gatherMiddleware());
        $this->assertNotContains('auth:sanctum', $route->gatherMiddleware());
    }
}
