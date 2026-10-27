<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'number' => 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'total_amount' => 0,
            'discount_amount' => 0,
            'final_amount' => 0,
            'paid_amount' => 0,
            'payment_status' => 'pending',
            'status' => 'pending',
        ];
    }

    /**
     * The order has a final amount set through its item snapshots.
     */
    public function withAmounts(float $total, ?float $final = null, float $paid = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => $total,
            'final_amount' => $final ?? $total,
            'discount_amount' => round($total - ($final ?? $total), 2),
            'paid_amount' => $paid,
            'payment_status' => $paid >= ($final ?? $total) ? 'paid' : ($paid > 0 ? 'partial' : 'pending'),
            'status' => $paid >= ($final ?? $total) ? 'completed' : 'pending',
        ]);
    }
}
