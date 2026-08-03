<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plant;
use Illuminate\Database\Eloquent\Collection;

interface PlantAdvisor
{
    /**
     * @param  array{environment: string, exposure: string, space_size: string, maintenance_availability: string, free_text_description: string}  $context
     * @param  Collection<int, Plant>  $candidatePlants
     * @return array<string, mixed>
     */
    public function advise(array $context, Collection $candidatePlants): array;
}
