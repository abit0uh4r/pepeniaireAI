<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plant>
 */
class PlantFactory extends Factory
{
    protected $model = Plant::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'species' => fake()->unique()->bothify('Botanica ???'),
            'description' => fake()->optional()->paragraph(),
            'environment' => fake()->randomElement(PlantEnvironment::cases()),
            'exposure' => fake()->randomElement(Exposure::cases()),
            'watering_level' => fake()->randomElement(Level::cases()),
            'maintenance_level' => fake()->randomElement(Level::cases()),
            'adult_height_cm' => fake()->numberBetween(20, 180),
            'adult_width_cm' => fake()->numberBetween(15, 120),
            'price' => number_format(fake()->randomFloat(2, 5, 500), 2, '.', ''),
            'stock_quantity' => fake()->numberBetween(0, 25),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => ['stock_quantity' => 0]);
    }
}
