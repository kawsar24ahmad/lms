<?php

namespace Database\Factories;

use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSetting>
 */
class SiteSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_name' => fake()->company(),
            'tagline' => fake()->catchPhrase(),
            'site_description' => fake()->paragraph(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'contact_address' => fake()->address(),
            'meta_title' => fake()->sentence(4),
            'meta_description' => fake()->sentence(12),
            'footer_text' => fake()->sentence(6),
        ];
    }
}
