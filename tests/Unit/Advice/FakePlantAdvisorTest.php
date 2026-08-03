<?php

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Models\Plant;
use App\Services\FakePlantAdvisor;
use App\Services\PlantAdvisor;
use Illuminate\Database\Eloquent\Collection;

function fakeAdviceContext(): array
{
    return [
        'environment' => PlantEnvironment::INDOOR->value,
        'exposure' => Exposure::PARTIAL_SHADE->value,
        'space_size' => SpaceSize::MEDIUM->value,
        'maintenance_availability' => Level::LOW->value,
        'free_text_description' => 'Une description de contexte suffisamment longue pour le fake.',
    ];
}

test('the fake advisor is bound by default without network access', function () {
    expect(app(PlantAdvisor::class))->toBeInstanceOf(FakePlantAdvisor::class);
});

test('the fake advisor is deterministic and only ranks supplied candidates', function () {
    $advisor = app(PlantAdvisor::class);
    $plants = new Collection([
        Plant::factory()->create(),
        Plant::factory()->create(),
    ]);

    $first = $advisor->advise(fakeAdviceContext(), $plants);
    $second = $advisor->advise(fakeAdviceContext(), $plants);

    expect($first)->toBe($second)
        ->and($first['recommendations'])->toHaveCount(2)
        ->and(array_column($first['recommendations'], 'plant_id'))->toBe($plants->modelKeys());
});

test('the advisor context excludes visitor contact details and deferred pet criteria', function () {
    $payload = fakeAdviceContext();

    expect($payload)->toHaveKeys([
        'environment',
        'exposure',
        'space_size',
        'maintenance_availability',
        'free_text_description',
    ])->and($payload)->not->toHaveKey('customer_name')
        ->and($payload)->not->toHaveKey('customer_email')
        ->and($payload)->not->toHaveKey('has_pets');
});
