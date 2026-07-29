<?php

declare(strict_types=1);

namespace App\Enums;

enum Exposure: string
{
    case SUN = 'SUN';
    case PARTIAL_SHADE = 'PARTIAL_SHADE';
    case SHADE = 'SHADE';

    public function label(): string
    {
        return match ($this) {
            self::SUN => 'Soleil direct',
            self::PARTIAL_SHADE => 'Mi-ombre',
            self::SHADE => 'Ombre',
        };
    }
}
