<?php

namespace Whilesmart\Payments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Payments\Enums\PaymentDirection;
use Whilesmart\Payments\Enums\PaymentMethod;
use Whilesmart\Payments\Enums\PaymentStatus;
use Whilesmart\Payments\Models\Payment;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'amount_cents' => $this->faker->numberBetween(1000, 10_000_000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'XAF', 'NGN', 'KES']),
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'method' => $this->faker->randomElement([
                PaymentMethod::Card->value,
                PaymentMethod::MobileMoney->value,
                PaymentMethod::BankTransfer->value,
            ]),
            'gateway' => $this->faker->randomElement(['stripe', 'paystack', 'flutterwave', 'wspay']),
            'gateway_reference' => 'ref_'.$this->faker->unique()->bothify('??####'),
            'succeeded_at' => now(),
        ];
    }
}
