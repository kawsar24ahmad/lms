<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalPrice = $this->faker->randomFloat(2, 50, 500);
        $discountAmount = $this->faker->randomFloat(2, 0, 50);
        $finalPrice = max($originalPrice - $discountAmount, 0);
        $paidAmount = $finalPrice > 0 ? max(min($this->faker->randomFloat(2, 0, $finalPrice), $finalPrice), 0) : 0.00;
        $dueAmount = max($finalPrice - $paidAmount, 0);

        return [
            'course_id' => Course::factory(),
            'student_id' => User::factory(),
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'payment_status' => $dueAmount <= 0 ? 'paid' : 'partial',
            'status' => 'active',
            'enrolled_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the enrollment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
