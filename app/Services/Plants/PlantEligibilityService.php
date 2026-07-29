<?php

declare(strict_types=1);

namespace App\Services\Plants;

use App\DTOs\Advice\AdviceContext;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Collection;

final class PlantEligibilityService
{
    /**
     * @return Collection<int, Plant>
     */
    public function eligiblePlants(AdviceContext $context): Collection
    {
        $limits = config("advice.eligibility.space_limits.{$context->spaceSize->value}");

        if (! is_array($limits)) {
            return new Collection;
        }

        $maxHeight = $limits['height_cm'] ?? null;
        $maxWidth = $limits['width_cm'] ?? null;

        if (! is_int($maxHeight) || ! is_int($maxWidth)) {
            return new Collection;
        }

        return Plant::query()
            ->active()
            ->inStock()
            ->orderBy('id')
            ->get()
            ->filter(fn (Plant $plant): bool => $this->matchesContext($plant, $context, $maxHeight, $maxWidth))
            ->values();
    }

    /**
     * @return list<int>
     */
    public function eligiblePlantIds(AdviceContext $context): array
    {
        return array_values(array_map(
            static fn (int|string $key): int => (int) $key,
            $this->eligiblePlants($context)->modelKeys(),
        ));
    }

    private function matchesContext(Plant $plant, AdviceContext $context, int $maxHeight, int $maxWidth): bool
    {
        if (! $this->matchesEnvironment($plant->environment, $context->environment)) {
            return false;
        }

        if (! $plant->exposureValues()->contains($context->exposure)) {
            return false;
        }

        if ($plant->adult_height_cm === null || $plant->adult_width_cm === null
            || $plant->adult_height_cm > $maxHeight || $plant->adult_width_cm > $maxWidth) {
            return false;
        }

        if ($this->levelRank($plant->maintenance_level) > $this->levelRank($context->maintenanceAvailability)) {
            return false;
        }

        return ! $context->hasPets || $plant->pet_safe === true;
    }

    private function matchesEnvironment(PlantEnvironment $plantEnvironment, PlantEnvironment $requestedEnvironment): bool
    {
        return $plantEnvironment === PlantEnvironment::BOTH || $plantEnvironment === $requestedEnvironment;
    }

    private function levelRank(Level $level): int
    {
        return match ($level) {
            Level::LOW => 1,
            Level::MEDIUM => 2,
            Level::HIGH => 3,
        };
    }
}
