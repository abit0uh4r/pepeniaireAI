<?php

use App\Enums\Exposure;
use App\Enums\PlantEnvironment;
use App\Models\Plant;

test('plant casts preserve enum values and exposure collections', function () {
    $plant = Plant::factory()->create([
        'environment' => PlantEnvironment::OUTDOOR,
        'exposure' => [Exposure::SUN, Exposure::PARTIAL_SHADE],
    ]);

    expect($plant->environment)->toBe(PlantEnvironment::OUTDOOR)
        ->and($plant->exposureValues()->all())->toBe([Exposure::SUN, Exposure::PARTIAL_SHADE]);
});

test('active and in-stock scopes only return eligible catalogue rows', function () {
    $available = Plant::factory()->create(['is_active' => true, 'stock_quantity' => 3]);
    Plant::factory()->inactive()->create();
    Plant::factory()->outOfStock()->create();

    expect(Plant::query()->active()->inStock()->pluck('id')->all())->toBe([$available->id]);
});
