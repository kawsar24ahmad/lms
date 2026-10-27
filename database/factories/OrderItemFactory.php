<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalPrice = fake()->randomFloat(2, 10, 500);
        $finalPrice = fake()->randomFloat(2, 0, $originalPrice);

        return [
            'order_id' => Order::factory(),
            'course_id' => Course::factory(),
            'original_price' => $originalPrice,
            'discount_amount' => round($originalPrice - $finalPrice, 2),
            'final_price' => $finalPrice,
        ];
    }
}
