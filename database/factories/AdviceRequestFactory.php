<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AdviceRequestStatus;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Models\AdviceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdviceRequest>
 */
class AdviceRequestFactory extends Factory
{
    protected $model = AdviceRequest::class;

    public function definition(): array
    {
        return [
            'customer_name' => fake()->optional()->name(),
            'customer_email' => fake()->optional()->safeEmail(),
            'environment' => fake()->randomElement([PlantEnvironment::INDOOR, PlantEnvironment::OUTDOOR]),
            'exposure' => fake()->randomElement(Exposure::cases()),
            'space_size' => fake()->randomElement(SpaceSize::cases()),
            'maintenance_availability' => fake()->randomElement(Level::cases()),
            'free_text_description' => fake()->paragraph(),
            'status' => AdviceRequestStatus::PENDING,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AdviceRequestStatus::COMPLETED,
            'processed_at' => now(),
        ]);
    }
}
