<?php

declare(strict_types=1);

namespace App\DTOs\Advice;

final readonly class ValidatedAdviceResult
{
    /** @param list<PlantRecommendationData> $recommendations */
    public function __construct(
        public string $spaceSummary,
        public string $generalAdvice,
        public array $recommendations,
    ) {}

    /** @return array{space_summary: string, general_advice: string, recommendations: list<array{plant_id: int, rank: int, reason: string}>} */
    public function toArray(): array
    {
        return [
            'space_summary' => $this->spaceSummary,
            'general_advice' => $this->generalAdvice,
            'recommendations' => array_map(
                static fn (PlantRecommendationData $recommendation): array => $recommendation->toArray(),
                $this->recommendations,
            ),
        ];
    }
}
