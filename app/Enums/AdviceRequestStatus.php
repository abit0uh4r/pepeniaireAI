<?php

declare(strict_types=1);

namespace App\Enums;

enum AdviceRequestStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::PROCESSING => 'En traitement',
            self::COMPLETED => 'Terminé',
            self::FAILED => 'Échec',
        };
    }
}
