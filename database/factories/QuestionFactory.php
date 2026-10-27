<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'question_text' => fake()->unique()->sentence().'?',
            'type' => 'multiple_choice',
            'points' => 1,
        ];
    }

    /**
     * Indicate that the question is a true/false question.
     */
    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'true_false',
        ]);
    }

    /**
     * Indicate that the question requires a free-text answer.
     */
    public function openAnswer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'open_answer',
        ]);
    }
}
