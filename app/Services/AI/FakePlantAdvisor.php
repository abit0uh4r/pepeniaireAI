<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Contracts\AI\PlantAdvisor;
use App\DTOs\Advice\AdviceContext;
use App\DTOs\Advice\AdviceResult;
use App\DTOs\Advice\PlantRecommendationData;

final class FakePlantAdvisor implements PlantAdvisor
{
    public function __construct(private readonly int $maxRecommendations = 3) {}

    /**
     * @param  list<int>  $candidatePlantIds
     */
    public function advise(AdviceContext $context, array $candidatePlantIds): AdviceResult
    {
        $candidatePlantIds = array_values(array_unique(array_filter(
            $candidatePlantIds,
            static fn (mixed $plantId): bool => is_int($plantId) && $plantId > 0,
        )));
        $candidatePlantIds = array_slice($candidatePlantIds, 0, max(0, $this->maxRecommendations));

        $recommendations = [];

        foreach ($candidatePlantIds as $index => $plantId) {
            $recommendations[] = new PlantRecommendationData(
                plantId: $plantId,
                rank: $index + 1,
                reason: 'Candidate compatible avec les critères de la demande.',
            );
        }

        $petAdvice = $context->hasPets
            ? 'La présence d’animaux sera prise en compte avec une exigence de sécurité explicite.'
            : 'Les recommandations pourront privilégier les plantes adaptées à votre disponibilité d’entretien.';

        return new AdviceResult(
            spaceSummary: sprintf(
                'Espace %s, environnement %s et exposition %s.',
                $context->spaceSize->label(),
                $context->environment->label(),
                $context->exposure->label(),
            ),
            generalAdvice: $petAdvice,
            recommendations: $recommendations,
        );
    }
}
