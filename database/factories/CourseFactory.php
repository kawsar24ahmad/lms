<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'category_id' => Category::factory(),
            'user_id' => User::factory()->instructor(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->regexify('[a-z0-9]{6}'),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->randomFloat(2, 0, 99),
            'status' => 'draft',
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the course is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the course is free.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
        ]);
    }

    /**
     * Give the course a discounted selling price.
     */
    public function discounted(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => Course::DISCOUNT_TYPE_PERCENT,
            'discount_amount' => fake()->randomFloat(2, 10, 40),
        ]);
    }

    /**
     * Attach a YouTube intro video.
     */
    public function introVideo(): static
    {
        return $this->state(fn (array $attributes) => [
            'intro_video_url' => 'https://www.youtube.com/watch?v='.Str::lower(Str::random(10)),
        ]);
    }
}
