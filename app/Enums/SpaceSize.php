<?php

declare(strict_types=1);

namespace App\Enums;

enum SpaceSize: string
{
    case SMALL = 'SMALL';
    case MEDIUM = 'MEDIUM';
    case LARGE = 'LARGE';

    public function label(): string
    {
        return match ($this) {
            self::SMALL => 'Petit',
            self::MEDIUM => 'Moyen',
            self::LARGE => 'Grand',
        };
    }
}
