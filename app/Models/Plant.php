<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use Database\Factories\PlantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'species',
    'description',
    'environment',
    'exposure',
    'watering_level',
    'maintenance_level',
    'adult_height_cm',
    'adult_width_cm',
    'price',
    'stock_quantity',
    'is_active',
])]
class Plant extends Model
{
    /** @use HasFactory<PlantFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'environment' => PlantEnvironment::class,
            'exposure' => Exposure::class,
            'watering_level' => Level::class,
            'maintenance_level' => Level::class,
            'adult_height_cm' => 'integer',
            'adult_width_cm' => 'integer',
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): void
    {
        $query->where('stock_quantity', '>', 0);
    }

    public function scopeSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($search): void {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('species', 'like', "%{$search}%");
        });
    }

    /** @return HasMany<PlantRecommendation, $this> */
    public function recommendations(): HasMany
    {
        return $this->hasMany(PlantRecommendation::class);
    }
}
