<?php

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Models\AdviceRequest;
use App\Models\Plant;
use App\Services\PlantEligibilityService;

function eligibilityRequest(array $overrides = []): AdviceRequest
{
    return AdviceRequest::factory()->create(array_merge([
        'environment' => PlantEnvironment::INDOOR,
        'exposure' => Exposure::PARTIAL_SHADE,
        'space_size' => SpaceSize::MEDIUM,
        'maintenance_availability' => Level::MEDIUM,
    ], $overrides));
}

function eligiblePlantAttributes(array $overrides = []): array
{
    return array_merge([
        'environment' => PlantEnvironment::INDOOR,
        'exposure' => Exposure::PARTIAL_SHADE,
        'maintenance_level' => Level::LOW,
        'adult_height_cm' => 80,
        'adult_width_cm' => 50,
        'stock_quantity' => 5,
        'is_active' => true,
    ], $overrides);
}

test('it returns active in-stock plants matching every deterministic constraint', function () {
    $request = eligibilityRequest();
    $plant = Plant::factory()->create(eligiblePlantAttributes());
    Plant::factory()->create(eligiblePlantAttributes(['is_active' => false]));
    Plant::factory()->create(eligiblePlantAttributes(['stock_quantity' => 0]));
    Plant::factory()->create(eligiblePlantAttributes(['environment' => PlantEnvironment::OUTDOOR]));
    Plant::factory()->create(eligiblePlantAttributes(['exposure' => Exposure::SUN]));

    $ids = app(PlantEligibilityService::class)->eligiblePlants($request)->modelKeys();

    expect($ids)->toBe([$plant->id]);
});

test('a plant supporting both environments is eligible for either request', function () {
    $request = eligibilityRequest(['environment' => PlantEnvironment::OUTDOOR]);
    $plant = Plant::factory()->create(eligiblePlantAttributes(['environment' => PlantEnvironment::BOTH]));

    expect(app(PlantEligibilityService::class)->eligiblePlants($request)->modelKeys())
        ->toBe([$plant->id]);
});

test('it excludes plants that exceed the requested space or maintenance level', function () {
    $request = eligibilityRequest();
    Plant::factory()->create(eligiblePlantAttributes(['adult_height_cm' => 121]));
    Plant::factory()->create(eligiblePlantAttributes(['maintenance_level' => Level::HIGH]));

    expect(app(PlantEligibilityService::class)->eligiblePlants($request))->toHaveCount(0);
});

test('it fails closed when the configured space limits are incomplete', function () {
    $request = eligibilityRequest();
    Plant::factory()->create(eligiblePlantAttributes());
    config()->set('advice.eligibility.space_limits.MEDIUM', ['height_cm' => '120']);

    expect(app(PlantEligibilityService::class)->eligiblePlants($request))->toHaveCount(0);
});

test('it orders candidates deterministically', function () {
    $request = eligibilityRequest();
    $first = Plant::factory()->create(eligiblePlantAttributes());
    $second = Plant::factory()->create(eligiblePlantAttributes());

    expect(app(PlantEligibilityService::class)->eligiblePlants($request)->modelKeys())
        ->toBe([$first->id, $second->id]);
});
