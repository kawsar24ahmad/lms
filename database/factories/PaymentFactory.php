<?php

namespace Database\Factories;

use App\Models\Order;
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
            'order_id' => Order::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'payment_method' => fake()->randomElement(['bank_transfer', 'card', 'cash']),
            'transaction_id' => fake()->unique()->uuid(),
            'status' => Payment::STATUS_PENDING,
            'paid_at' => null,
        ];
    }

    /**
     * A verified payment, settled now.
     */
    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_SUCCEEDED,
            'paid_at' => now(),
        ]);
    }

    /**
     * A failed payment attempt.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_FAILED,
            'paid_at' => null,
        ]);
    }
}
