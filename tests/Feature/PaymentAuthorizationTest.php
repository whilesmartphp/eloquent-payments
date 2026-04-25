<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\TestInvoice;
use Tests\TestCase;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;
use Whilesmart\Payments\Enums\PaymentDirection;
use Whilesmart\Payments\Enums\PaymentStatus;
use Whilesmart\Payments\Models\Payment;

class PaymentAuthorizationTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        Schema::create('workspaces', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(OwnerAuthorizer::class, new class implements OwnerAuthorizer
        {
            public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
            {
                return false;
            }

            public function scope(Builder $query, ?Authenticatable $user, string $ownerTypeColumn = 'owner_type', string $ownerIdColumn = 'owner_id'): Builder
            {
                return $query->whereRaw('0 = 1');
            }
        });
    }

    #[Test]
    public function store_returns_403_when_authorizer_denies(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-A', 'total_cents' => 1_000]);

        $this->postJson('/api/payments', [
            'payable_type' => TestInvoice::class,
            'payable_id' => $invoice->id,
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => 1,
            'amount_cents' => 1_000,
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'gateway' => 'stripe',
            'gateway_reference' => 'ch_denied',
        ])->assertForbidden();
    }

    #[Test]
    public function store_skips_authorizer_when_owner_fields_are_absent(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-A', 'total_cents' => 1_000]);

        $this->postJson('/api/payments', [
            'payable_type' => TestInvoice::class,
            'payable_id' => $invoice->id,
            'amount_cents' => 1_000,
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'gateway' => 'stripe',
            'gateway_reference' => 'ch_owner_less',
        ])->assertCreated();
    }

    #[Test]
    public function show_returns_403_when_record_has_owner_and_authorizer_denies(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-A', 'total_cents' => 1_000]);
        $payment = Payment::create([
            'payable_type' => TestInvoice::class,
            'payable_id' => $invoice->id,
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => 1,
            'amount_cents' => 1_000,
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'gateway_reference' => 'ch_show',
        ]);

        $this->getJson("/api/payments/{$payment->id}")->assertForbidden();
    }

    #[Test]
    public function destroy_returns_403_when_authorizer_denies(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-A', 'total_cents' => 1_000]);
        $payment = Payment::create([
            'payable_type' => TestInvoice::class,
            'payable_id' => $invoice->id,
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => 1,
            'amount_cents' => 1_000,
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'gateway_reference' => 'ch_destroy',
        ]);

        $this->deleteJson("/api/payments/{$payment->id}")->assertForbidden();

        $this->assertNotNull($payment->fresh());
    }

    #[Test]
    public function index_applies_scope_from_authorizer(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-A', 'total_cents' => 1_000]);
        Payment::create([
            'payable_type' => TestInvoice::class,
            'payable_id' => $invoice->id,
            'owner_type' => 'App\\Models\\Workspace',
            'owner_id' => 1,
            'amount_cents' => 1_000,
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'gateway_reference' => 'ch_index',
        ]);

        $response = $this->getJson('/api/payments')->assertOk();

        $this->assertSame(0, $response->json('data.meta.total'));
    }
}
