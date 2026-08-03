<?php

use App\Models\Plant;
use Database\Seeders\PlantSeeder;

test('plant seeder provisions a varied demonstration catalogue idempotently', function () {
    $this->seed(PlantSeeder::class);
    $this->seed(PlantSeeder::class);

    $plants = Plant::query()->get();

    expect($plants)->toHaveCount(23)
        ->and($plants->pluck('environment')->map(fn ($environment): string => $environment->value)->unique()->sort()->values()->all())
        ->toBe(['BOTH', 'INDOOR', 'OUTDOOR'])
        ->and($plants->where('stock_quantity', '>', 0))->toHaveCount(23)
        ->and($plants->where('pet_safe', true))->not->toBeEmpty()
        ->and($plants->whereNull('pet_safe'))->not->toBeEmpty();
});
