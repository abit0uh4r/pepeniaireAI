<?php

declare(strict_types=1);

namespace App\Enums;

enum AdviceFailure: string
{
    case NO_ELIGIBLE_PLANTS = 'NO_ELIGIBLE_PLANTS';
    case AI_ERROR = 'AI_ERROR';

    public function message(): string
    {
        return match ($this) {
            self::NO_ELIGIBLE_PLANTS => 'Aucune plante ne correspond aux critères indiqués.',
            self::AI_ERROR => 'Le service de conseil est temporairement indisponible.',
        };
    }
}
