<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'title' => fake()->unique()->sentence(3),
            'content' => fake()->paragraphs(2, true),
            'video_url' => null,
            'duration_minutes' => fake()->numberBetween(5, 60),
            'position' => 0,
            'is_preview' => false,
        ];
    }

    /**
     * Indicate that the lesson is available as a free preview.
     */
    public function preview(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_preview' => true,
        ]);
    }

    /**
     * Indicate that the lesson has a video attached.
     */
    public function withVideo(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_url' => 'https://storage.googleapis.com/gtv-videos-bucket/sample/'.fake()->randomElement([
                'BigBuckBunny',
                'ElephantsDream',
                'ForBiggerBlazes',
                'ForBiggerEscapes',
                'ForBiggerFun',
                'ForBiggerJoyrides',
                'ForBiggerMeltdowns',
                'Sintel',
            ]).'.mp4',
        ]);
    }
}
