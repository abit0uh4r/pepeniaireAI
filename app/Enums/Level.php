<?php

declare(strict_types=1);

namespace App\Enums;

enum Level: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Faible',
            self::MEDIUM => 'Moyen',
            self::HIGH => 'Élevé',
        };
    }
}
