<?php

use App\Contracts\AI\PlantAdvisor;
use App\DTOs\Advice\AdviceContext;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Services\AI\FakePlantAdvisor;

function fakeAdviceContext(bool $hasPets = false): AdviceContext
{
    return new AdviceContext(
        environment: PlantEnvironment::INDOOR,
        exposure: Exposure::PARTIAL_SHADE,
        spaceSize: SpaceSize::MEDIUM,
        maintenanceAvailability: Level::LOW,
        hasPets: $hasPets,
        freeTextDescription: 'Une description de contexte suffisamment longue pour le fake.',
    );
}

test('the fake advisor is bound by default without network access', function () {
    expect(app(PlantAdvisor::class))->toBeInstanceOf(FakePlantAdvisor::class);
});

test('the fake advisor is deterministic and only ranks supplied candidates', function () {
    $advisor = app(PlantAdvisor::class);
    $context = fakeAdviceContext();

    $first = $advisor->advise($context, [12, 4, 12, 0, -1]);
    $second = $advisor->advise($context, [12, 4, 12, 0, -1]);

    expect($first->toArray())->toBe($second->toArray())
        ->and($first->toArray()['recommendations'])->toHaveCount(2)
        ->and(array_column($first->toArray()['recommendations'], 'plant_id'))->toBe([12, 4]);
});

test('the advice context excludes visitor contact details from the advisor payload', function () {
    $payload = fakeAdviceContext(hasPets: true)->toAdvisorPayload();

    expect($payload)->toHaveKeys([
        'environment',
        'exposure',
        'space_size',
        'maintenance_availability',
        'has_pets',
        'free_text_description',
    ])->and($payload)->not->toHaveKey('customer_name')
        ->and($payload)->not->toHaveKey('customer_email');
});
