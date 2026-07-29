<?php

use App\DTOs\Advice\AdviceContext;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Models\Plant;
use App\Services\Plants\PlantEligibilityService;

function eligibilityContext(
    PlantEnvironment $environment = PlantEnvironment::INDOOR,
    Exposure $exposure = Exposure::PARTIAL_SHADE,
    SpaceSize $spaceSize = SpaceSize::MEDIUM,
    Level $maintenanceAvailability = Level::MEDIUM,
    bool $hasPets = false,
): AdviceContext {
    return new AdviceContext($environment, $exposure, $spaceSize, $maintenanceAvailability, $hasPets, '');
}

function eligiblePlantAttributes(array $overrides = []): array
{
    return array_merge([
        'environment' => PlantEnvironment::INDOOR,
        'exposure' => [Exposure::PARTIAL_SHADE],
        'maintenance_level' => Level::LOW,
        'adult_height_cm' => 80,
        'adult_width_cm' => 50,
        'pet_safe' => true,
        'stock_quantity' => 5,
        'is_active' => true,
    ], $overrides);
}

test('it returns active in-stock plants matching every deterministic constraint', function () {
    $plant = Plant::factory()->create(eligiblePlantAttributes());
    Plant::factory()->create(eligiblePlantAttributes(['is_active' => false]));
    Plant::factory()->create(eligiblePlantAttributes(['stock_quantity' => 0]));
    Plant::factory()->create(eligiblePlantAttributes(['environment' => PlantEnvironment::OUTDOOR]));
    Plant::factory()->create(eligiblePlantAttributes(['exposure' => [Exposure::SUN]]));

    $ids = app(PlantEligibilityService::class)->eligiblePlantIds(eligibilityContext());

    expect($ids)->toBe([$plant->id]);
});

test('a plant supporting both environments is eligible for either request', function () {
    $plant = Plant::factory()->create(eligiblePlantAttributes(['environment' => PlantEnvironment::BOTH]));

    $service = app(PlantEligibilityService::class);

    expect($service->eligiblePlantIds(eligibilityContext(PlantEnvironment::OUTDOOR)))->toBe([$plant->id]);
});

test('it excludes plants that exceed the requested space or maintenance level', function () {
    $tooTall = Plant::factory()->create(eligiblePlantAttributes(['adult_height_cm' => 121]));
    $tooDemanding = Plant::factory()->create(eligiblePlantAttributes(['maintenance_level' => Level::HIGH]));

    $ids = app(PlantEligibilityService::class)->eligiblePlantIds(eligibilityContext());

    expect($ids)->toBe([])
        ->not->toContain($tooTall->id)
        ->not->toContain($tooDemanding->id);
});

test('a request with pets only accepts explicitly pet-safe plants', function () {
    $safe = Plant::factory()->create(eligiblePlantAttributes(['pet_safe' => true]));
    Plant::factory()->create(eligiblePlantAttributes(['pet_safe' => false]));
    Plant::factory()->create(eligiblePlantAttributes(['pet_safe' => null]));

    $ids = app(PlantEligibilityService::class)->eligiblePlantIds(eligibilityContext(hasPets: true));

    expect($ids)->toBe([$safe->id]);
});

test('a request without pets permits unknown pet safety', function () {
    $unknown = Plant::factory()->create(eligiblePlantAttributes(['pet_safe' => null]));

    expect(app(PlantEligibilityService::class)->eligiblePlantIds(eligibilityContext()))
        ->toContain($unknown->id);
});

test('it returns no candidates when no plant is eligible', function () {
    expect(app(PlantEligibilityService::class)->eligiblePlants(eligibilityContext()))
        ->toHaveCount(0);
});

test('it fails closed when the configured space limits are incomplete', function () {
    Plant::factory()->create(eligiblePlantAttributes());
    config()->set('advice.eligibility.space_limits.MEDIUM', ['height_cm' => '120']);

    expect(app(PlantEligibilityService::class)->eligiblePlantIds(eligibilityContext()))
        ->toBe([]);
});

test('it orders candidate identifiers deterministically', function () {
    $first = Plant::factory()->create(eligiblePlantAttributes());
    $second = Plant::factory()->create(eligiblePlantAttributes());

    expect(app(PlantEligibilityService::class)->eligiblePlantIds(eligibilityContext()))
        ->toBe([$first->id, $second->id]);
});
