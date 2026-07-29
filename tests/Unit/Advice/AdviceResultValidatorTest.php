<?php

use App\DTOs\Advice\AdviceResult;
use App\DTOs\Advice\PlantRecommendationData;
use App\Models\Plant;
use App\Services\Advice\AdviceResultValidator;

test('it retains only unique active in-stock candidate recommendations up to the configured limit', function () {
    config()->set('advice.max_recommendations', 2);
    $first = Plant::factory()->create(['stock_quantity' => 4]);
    $second = Plant::factory()->create(['stock_quantity' => 3]);
    $inactive = Plant::factory()->create(['is_active' => false]);
    $empty = Plant::factory()->create(['stock_quantity' => 0]);

    $result = new AdviceResult(' Espace moyen ', ' Conseil général ', [
        new PlantRecommendationData($first->id, 1, '  Première raison  '),
        new PlantRecommendationData(999999, 2, 'ID inventé'),
        new PlantRecommendationData($first->id, 1, 'Doublon'),
        new PlantRecommendationData($inactive->id, 2, 'Inactive'),
        new PlantRecommendationData($empty->id, 2, 'Rupture'),
        new PlantRecommendationData($second->id, 2, 'Deuxième raison'),
        new PlantRecommendationData(123456, 3, 'Au-delà de la limite'),
    ]);

    $validated = app(AdviceResultValidator::class)->validate(
        $result,
        [$first->id, $second->id, $inactive->id, $empty->id],
    );

    expect($validated->spaceSummary)->toBe('Espace moyen')
        ->and($validated->generalAdvice)->toBe('Conseil général')
        ->and($validated->recommendations)->toHaveCount(2)
        ->and(array_map(fn ($recommendation): int => $recommendation->plantId, $validated->recommendations))
        ->toBe([$first->id, $second->id])
        ->and($validated->recommendations[0]->reason)->toBe('Première raison');
});

test('it rejects invalid ranks and empty explanations', function () {
    $plant = Plant::factory()->create();

    $validated = app(AdviceResultValidator::class)->validate(
        new AdviceResult('', '', [
            new PlantRecommendationData($plant->id, 0, 'Rang invalide'),
            new PlantRecommendationData($plant->id, 1, '   '),
        ]),
        [$plant->id],
    );

    expect($validated->recommendations)->toBe([]);
});

test('it handles an empty candidate list without querying an advisor result into recommendations', function () {
    $validated = app(AdviceResultValidator::class)->validate(
        new AdviceResult('Résumé', 'Conseil', [new PlantRecommendationData(1, 1, 'Inaccessible')]),
        [],
    );

    expect($validated->recommendations)->toBe([]);
});
