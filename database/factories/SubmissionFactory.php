<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => Assignment::factory(),
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
            'file_path' => null,
            'score' => null,
            'feedback' => null,
            'status' => 'pending',
            'submitted_at' => now(),
        ];
    }

    /**
     * Indicate that the submission was graded.
     */
    public function graded(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(0, 100),
            'feedback' => fake()->sentence(),
            'status' => 'graded',
        ]);
    }
}
