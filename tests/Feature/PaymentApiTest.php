<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\TestInvoice;
use Tests\TestCase;
use Whilesmart\Payments\Enums\PaymentDirection;
use Whilesmart\Payments\Enums\PaymentStatus;
use Whilesmart\Payments\Models\Payment;

class PaymentApiTest extends TestCase
{
    #[Test]
    public function post_creates_a_payment(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-A', 'total_cents' => 1_000]);

        $response = $this->postJson('/api/payments', [
            'payable_type' => TestInvoice::class,
            'payable_id' => $invoice->id,
            'amount_cents' => 1_000,
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'gateway' => 'stripe',
            'gateway_reference' => 'ch_api_1',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.amount_cents', 1_000);
        $this->assertSame(1, Payment::count());
    }

    #[Test]
    public function post_rejects_without_required_fields(): void
    {
        $response = $this->postJson('/api/payments', [
            // payable_type + payable_id + amount_cents + currency missing
            'method' => 'card',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payable_type', 'payable_id', 'amount_cents', 'currency']);
    }

    #[Test]
    public function unique_gateway_reference_rejects_duplicates_at_the_database_layer(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-B', 'total_cents' => 1_000]);

        $invoice->recordPayment([
            'amount_cents' => 1_000, 'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'gateway' => 'stripe',
            'gateway_reference' => 'ch_dup',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $invoice->recordPayment([
            'amount_cents' => 1_000, 'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'gateway' => 'stripe',
            'gateway_reference' => 'ch_dup',
        ]);
    }

    #[Test]
    public function index_filters_by_payable(): void
    {
        $a = TestInvoice::create(['number' => 'A', 'total_cents' => 0]);
        $b = TestInvoice::create(['number' => 'B', 'total_cents' => 0]);

        $a->recordPayment(['amount_cents' => 1, 'currency' => 'USD', 'status' => 'succeeded', 'direction' => 'inbound']);
        $a->recordPayment(['amount_cents' => 1, 'currency' => 'USD', 'status' => 'succeeded', 'direction' => 'inbound']);
        $b->recordPayment(['amount_cents' => 1, 'currency' => 'USD', 'status' => 'succeeded', 'direction' => 'inbound']);

        $response = $this->getJson('/api/payments?payable_type='.urlencode(TestInvoice::class).'&payable_id='.$a->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.meta.total', 2);
    }

    #[Test]
    public function index_filters_by_direction(): void
    {
        $invoice = TestInvoice::create(['number' => 'X', 'total_cents' => 0]);
        $invoice->recordPayment(['amount_cents' => 1, 'currency' => 'USD', 'status' => 'succeeded', 'direction' => 'inbound']);
        $invoice->recordPayment(['amount_cents' => 1, 'currency' => 'USD', 'status' => 'succeeded', 'direction' => 'outbound']);

        $response = $this->getJson('/api/payments?direction=outbound');

        $response->assertStatus(200);
        $response->assertJsonPath('data.meta.total', 1);
    }
}
