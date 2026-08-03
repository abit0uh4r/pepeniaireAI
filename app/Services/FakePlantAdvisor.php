<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Exposure;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Collection;

final class FakePlantAdvisor implements PlantAdvisor
{
    public function __construct(private readonly int $maxRecommendations = 3) {}

    /**
     * @param  array{environment: string, exposure: string, space_size: string, maintenance_availability: string, free_text_description: string}  $context
     * @param  Collection<int, Plant>  $candidatePlants
     * @return array{space_summary: string, general_advice: string, recommendations: list<array{plant_id: int, rank: int, reason: string}>}
     */
    public function advise(array $context, Collection $candidatePlants): array
    {
        $recommendations = $candidatePlants
            ->unique(fn (Plant $plant): int => $plant->getKey())
            ->take(max(0, $this->maxRecommendations))
            ->values()
            ->map(static fn (Plant $plant, int $index): array => [
                'plant_id' => $plant->getKey(),
                'rank' => $index + 1,
                'reason' => 'Candidate compatible avec les critères de la demande.',
            ])
            ->all();

        return [
            'space_summary' => sprintf(
                'Espace %s, environnement %s et exposition %s.',
                SpaceSize::from($context['space_size'])->label(),
                PlantEnvironment::from($context['environment'])->label(),
                Exposure::from($context['exposure'])->label(),
            ),
            'general_advice' => 'Les recommandations privilégient les plantes adaptées à votre espace et à votre disponibilité d’entretien.',
            'recommendations' => $recommendations,
        ];
    }
}
