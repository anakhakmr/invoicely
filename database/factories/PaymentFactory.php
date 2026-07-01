<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'stripe_payment_intent_id' => 'pi_'.fake()->unique()->bothify('##########????'),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'status' => fake()->randomElement(PaymentStatus::cases()),
        ];
    }
}
