<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PlantRecommendationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'advice_request_id',
    'plant_id',
    'rank',
    'reason',
    'stock_quantity_snapshot',
])]
class PlantRecommendation extends Model
{
    /** @use HasFactory<PlantRecommendationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'stock_quantity_snapshot' => 'integer',
        ];
    }

    /** @return BelongsTo<AdviceRequest, $this> */
    public function adviceRequest(): BelongsTo
    {
        return $this->belongsTo(AdviceRequest::class);
    }

    /** @return BelongsTo<Plant, $this> */
    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class)->withTrashed();
    }
}
