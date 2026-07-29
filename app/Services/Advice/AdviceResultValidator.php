<?php

declare(strict_types=1);

namespace App\Services\Advice;

use App\DTOs\Advice\AdviceResult;
use App\DTOs\Advice\PlantRecommendationData;
use App\DTOs\Advice\ValidatedAdviceResult;
use App\Models\Plant;

final class AdviceResultValidator
{
    /**
     * Invalid recommendation entries are discarded; valid entries are retained up to the configured limit.
     *
     * @param  list<int>  $candidatePlantIds
     */
    public function validate(AdviceResult $result, array $candidatePlantIds): ValidatedAdviceResult
    {
        $candidatePlantIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $candidatePlantIds),
            static fn (int $id): bool => $id > 0,
        )));

        $plants = Plant::query()
            ->active()
            ->inStock()
            ->whereKey($candidatePlantIds)
            ->get()
            ->keyBy(fn (Plant $plant): int => $plant->getKey());

        $maxRecommendations = max(0, (int) config('advice.max_recommendations', 3));
        $seenPlantIds = [];
        $recommendations = [];

        foreach ($result->recommendations as $recommendation) {
            $reason = trim($recommendation->reason);

            if ($recommendation->plantId <= 0
                || ! in_array($recommendation->plantId, $candidatePlantIds, true)
                || ! $plants->has($recommendation->plantId)
                || in_array($recommendation->plantId, $seenPlantIds, true)
                || $recommendation->rank < 1
                || $recommendation->rank > $maxRecommendations
                || $reason === '') {
                continue;
            }

            $seenPlantIds[] = $recommendation->plantId;
            $recommendations[] = new PlantRecommendationData(
                plantId: $recommendation->plantId,
                rank: $recommendation->rank,
                reason: mb_substr($reason, 0, 2000),
            );

            if (count($recommendations) >= $maxRecommendations) {
                break;
            }
        }

        return new ValidatedAdviceResult(
            spaceSummary: mb_substr(trim($result->spaceSummary), 0, 5000),
            generalAdvice: mb_substr(trim($result->generalAdvice), 0, 10000),
            recommendations: $recommendations,
        );
    }
}
