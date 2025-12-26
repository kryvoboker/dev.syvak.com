<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slug>
 */
class SlugFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sluggable_type' => $this->faker->word(),
            'sluggable_id'   => $this->faker->randomNumber(),
            'language_id'    => $this->faker->randomNumber(),
            'slug'           => $this->faker->slug(),
        ];
    }
}
