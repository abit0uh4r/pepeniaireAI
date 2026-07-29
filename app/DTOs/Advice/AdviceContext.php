<?php

declare(strict_types=1);

namespace App\DTOs\Advice;

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use App\Models\AdviceRequest;

final readonly class AdviceContext
{
    public function __construct(
        public PlantEnvironment $environment,
        public Exposure $exposure,
        public SpaceSize $spaceSize,
        public Level $maintenanceAvailability,
        public bool $hasPets,
        public string $freeTextDescription,
    ) {}

    public static function fromRequest(AdviceRequest $request): self
    {
        return new self(
            environment: $request->environment,
            exposure: $request->exposure,
            spaceSize: $request->space_size,
            maintenanceAvailability: $request->maintenance_availability,
            hasPets: $request->has_pets,
            freeTextDescription: $request->free_text_description,
        );
    }

    /**
     * @return array{environment: string, exposure: string, space_size: string, maintenance_availability: string, has_pets: bool, free_text_description: string}
     */
    public function toAdvisorPayload(): array
    {
        return [
            'environment' => $this->environment->value,
            'exposure' => $this->exposure->value,
            'space_size' => $this->spaceSize->value,
            'maintenance_availability' => $this->maintenanceAvailability->value,
            'has_pets' => $this->hasPets,
            'free_text_description' => $this->freeTextDescription,
        ];
    }
}
