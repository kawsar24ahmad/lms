<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'title' => 'Assignment '.fake()->unique()->numberBetween(1, 100),
            'description' => fake()->paragraph(),
            'max_score' => 100,
            'due_date' => null,
        ];
    }

    /**
     * Indicate that the assignment has a due date.
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => now()->addDays(fake()->numberBetween(1, 14)),
        ]);
    }
}
