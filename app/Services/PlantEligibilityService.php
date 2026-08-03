<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Models\AdviceRequest;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Collection;

final class PlantEligibilityService
{
    /** @return Collection<int, Plant> */
    public function eligiblePlants(AdviceRequest $adviceRequest): Collection
    {
        return Plant::query()
            ->active()
            ->inStock()
            ->where('exposure', $adviceRequest->exposure->value)
            ->orderBy('id')
            ->get()
            ->filter(fn (Plant $plant): bool => $this->isEligible($plant, $adviceRequest))
            ->values();
    }

    public function isEligible(Plant $plant, AdviceRequest $adviceRequest): bool
    {
        $limits = config("advice.eligibility.space_limits.{$adviceRequest->space_size->value}");
        $maxHeight = is_array($limits) ? ($limits['height_cm'] ?? null) : null;
        $maxWidth = is_array($limits) ? ($limits['width_cm'] ?? null) : null;

        if (! is_int($maxHeight) || ! is_int($maxWidth)
            || ! $plant->is_active
            || $plant->stock_quantity <= 0
            || $plant->exposure !== $adviceRequest->exposure) {
            return false;
        }

        if (! $this->matchesEnvironment($plant->environment, $adviceRequest->environment)) {
            return false;
        }

        if ($plant->adult_height_cm === null || $plant->adult_width_cm === null
            || $plant->adult_height_cm > $maxHeight || $plant->adult_width_cm > $maxWidth) {
            return false;
        }

        return $this->levelRank($plant->maintenance_level)
            <= $this->levelRank($adviceRequest->maintenance_availability);
    }

    private function matchesEnvironment(
        PlantEnvironment $plantEnvironment,
        PlantEnvironment $requestedEnvironment,
    ): bool {
        return $plantEnvironment === PlantEnvironment::BOTH
            || $plantEnvironment === $requestedEnvironment;
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
