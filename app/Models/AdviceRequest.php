<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdviceRequestStatus;
use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use Database\Factories\AdviceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'public_token',
    'customer_name',
    'customer_phone',
    'environment',
    'exposure',
    'space_size',
    'maintenance_availability',
    'free_text_description',
    'status',
    'space_summary',
    'avoid_items',
    'general_advice',
    'failure_message',
    'processing_started_at',
    'processed_at',
])]
class AdviceRequest extends Model
{
    /** @use HasFactory<AdviceRequestFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            $request->public_token ??= self::generatePublicToken();
            $request->status ??= AdviceRequestStatus::PENDING;
        });
    }

    protected function casts(): array
    {
        return [
            'environment' => PlantEnvironment::class,
            'exposure' => Exposure::class,
            'space_size' => SpaceSize::class,
            'maintenance_availability' => Level::class,
            'status' => AdviceRequestStatus::class,
            'avoid_items' => 'array',
            'processing_started_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public static function generatePublicToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', AdviceRequestStatus::PENDING->value);
    }

    /** @return HasMany<PlantRecommendation, $this> */
    public function recommendations(): HasMany
    {
        return $this->hasMany(PlantRecommendation::class)->orderBy('rank');
    }
}
