<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<QuizAttempt>
     */
    protected $model = QuizAttempt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'user_id' => User::factory(),
            'answers' => [],
            'score' => null,
            'status' => 'in_progress',
            'submitted_at' => null,
        ];
    }

    /**
     * A submitted attempt that passed.
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(70, 100),
            'status' => 'passed',
            'submitted_at' => now(),
        ]);
    }

    /**
     * A submitted attempt that failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => fake()->numberBetween(0, 50),
            'status' => 'failed',
            'submitted_at' => now(),
        ]);
    }
}
