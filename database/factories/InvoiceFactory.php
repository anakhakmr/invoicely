<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'invoice_number' => fake()->unique()->numerify('INV-#####'),
            'total' => fake()->randomFloat(2, 50, 5000),
            'status' => fake()->randomElement(InvoiceStatus::cases()),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'stripe_checkout_session_id' => null,
        ];
    }
}
