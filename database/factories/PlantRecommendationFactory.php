<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AdviceRequest;
use App\Models\Plant;
use App\Models\PlantRecommendation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlantRecommendation> */
class PlantRecommendationFactory extends Factory
{
    protected $model = PlantRecommendation::class;

    public function definition(): array
    {
        return [
            'advice_request_id' => AdviceRequest::factory(),
            'plant_id' => Plant::factory(),
            'rank' => 1,
            'reason' => fake()->sentence(),
            'stock_quantity_snapshot' => 5,
        ];
    }
}
