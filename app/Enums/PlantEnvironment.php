<?php

declare(strict_types=1);

namespace App\Enums;

enum PlantEnvironment: string
{
    case INDOOR = 'INDOOR';
    case OUTDOOR = 'OUTDOOR';
    case BOTH = 'BOTH';

    public function label(): string
    {
        return match ($this) {
            self::INDOOR => 'Intérieur',
            self::OUTDOOR => 'Extérieur',
            self::BOTH => 'Intérieur et extérieur',
        };
    }
}
