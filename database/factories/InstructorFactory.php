<?php

namespace Database\Factories;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instructor>
 */
class InstructorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->instructor(),
            'designation' => fake()->randomElement([
                'Senior Lecturer',
                'Associate Professor',
                'Lead Developer',
                'Principal Engineer',
                'Consultant',
            ]),
            'qualification' => fake()->randomElement([
                'MSc Computer Science',
                'PhD Software Engineering',
                'BSc Information Systems',
                'AWS Certified Solutions Architect',
            ]),
            'bio' => fake()->paragraphs(2, true),
        ];
    }
}
