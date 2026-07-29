<?php

declare(strict_types=1);

namespace App\DTOs\Advice;

final readonly class PlantRecommendationData
{
    public function __construct(
        public int $plantId,
        public int $rank,
        public string $reason,
    ) {}

    /**
     * @return array{plant_id: int, rank: int, reason: string}
     */
    public function toArray(): array
    {
        return [
            'plant_id' => $this->plantId,
            'rank' => $this->rank,
            'reason' => $this->reason,
        ];
    }
}
