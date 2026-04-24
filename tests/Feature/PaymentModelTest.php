<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\SimpleSubscription;
use Tests\Support\TestInvoice;
use Tests\TestCase;
use Whilesmart\Payments\Enums\PaymentDirection;
use Whilesmart\Payments\Enums\PaymentStatus;
use Whilesmart\Payments\Models\Payment;

class PaymentModelTest extends TestCase
{
    #[Test]
    public function it_records_a_payment_against_a_payable(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-1', 'total_cents' => 100_000]);

        $payment = $invoice->recordPayment([
            'amount_cents' => 100_000,
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'method' => 'card',
            'gateway' => 'stripe',
            'gateway_reference' => 'ch_test_1',
            'succeeded_at' => now(),
        ]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame($invoice->id, $payment->payable_id);
        $this->assertSame(PaymentStatus::Succeeded, $payment->status);
        $this->assertSame(PaymentDirection::Inbound, $payment->direction);
    }

    #[Test]
    public function cumulative_succeeded_inbound_payments_reflect_on_the_payable(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-2', 'total_cents' => 100_000]);

        $invoice->recordPayment([
            'amount_cents' => 40_000, 'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'succeeded_at' => now(),
        ]);

        $this->assertSame(40_000, $invoice->fresh()->amount_paid_cents);
        $this->assertNull($invoice->fresh()->paid_at);

        $invoice->recordPayment([
            'amount_cents' => 60_000, 'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'succeeded_at' => now(),
        ]);

        $this->assertSame(100_000, $invoice->fresh()->amount_paid_cents);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    #[Test]
    public function failed_and_cancelled_payments_do_not_count_toward_amount_paid(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-3', 'total_cents' => 50_000]);

        foreach ([PaymentStatus::Failed, PaymentStatus::Cancelled, PaymentStatus::Pending] as $status) {
            $invoice->recordPayment([
                'amount_cents' => 50_000, 'currency' => 'USD',
                'status' => $status->value,
                'direction' => PaymentDirection::Inbound->value,
            ]);
        }

        $this->assertSame(0, $invoice->fresh()->amount_paid_cents);
    }

    #[Test]
    public function outbound_payments_do_not_count_toward_amount_paid(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-4', 'total_cents' => 10_000]);

        $invoice->recordPayment([
            'amount_cents' => 10_000, 'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Outbound->value,
            'succeeded_at' => now(),
        ]);

        $this->assertSame(0, $invoice->fresh()->amount_paid_cents);
    }

    #[Test]
    public function reflection_is_a_silent_noop_when_payable_has_no_amount_paid_cents_column(): void
    {
        $sub = SimpleSubscription::create(['plan' => 'pro']);

        $payment = $sub->recordPayment([
            'amount_cents' => 1000, 'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'succeeded_at' => now(),
        ]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame(1, $sub->payments()->count());
    }

    #[Test]
    public function reflection_can_be_disabled_via_config(): void
    {
        config()->set('payments.auto_reflect_on_payable', false);

        $invoice = TestInvoice::create(['number' => 'INV-5', 'total_cents' => 10_000]);

        $invoice->recordPayment([
            'amount_cents' => 10_000, 'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'succeeded_at' => now(),
        ]);

        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(0, $invoice->fresh()->amount_paid_cents);
        $this->assertNull($invoice->fresh()->paid_at);
    }

    #[Test]
    public function parent_payment_id_links_refunds_to_the_original_charge(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-6', 'total_cents' => 5_000]);

        $original = $invoice->recordPayment([
            'amount_cents' => 5_000, 'currency' => 'USD',
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'succeeded_at' => now(),
        ]);

        $refund = $invoice->recordPayment([
            'amount_cents' => 5_000, 'currency' => 'USD',
            'status' => PaymentStatus::Refunded->value,
            'direction' => PaymentDirection::Outbound->value,
            'parent_payment_id' => $original->id,
            'refunded_at' => now(),
        ]);

        $this->assertSame($original->id, $refund->parent_payment_id);
        $this->assertSame($original->id, $refund->parent->id);
        $this->assertTrue($original->refunds->contains($refund));
    }

    #[Test]
    public function factory_produces_a_valid_payment_row(): void
    {
        $invoice = TestInvoice::create(['number' => 'INV-F', 'total_cents' => 0]);

        $payment = Payment::factory()->create([
            'payable_type' => TestInvoice::class,
            'payable_id' => $invoice->id,
        ]);

        $this->assertTrue($payment->exists);
        $this->assertGreaterThan(0, $payment->amount_cents);
        $this->assertSame(3, strlen($payment->currency));
    }
}
