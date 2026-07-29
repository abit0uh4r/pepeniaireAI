<?php

declare(strict_types=1);

namespace App\Contracts\AI;

use App\DTOs\Advice\AdviceContext;
use App\DTOs\Advice\AdviceResult;

interface PlantAdvisor
{
    /**
     * @param  list<int>  $candidatePlantIds
     */
    public function advise(AdviceContext $context, array $candidatePlantIds): AdviceResult;
}
