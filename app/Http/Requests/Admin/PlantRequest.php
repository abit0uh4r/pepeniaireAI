<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Models\Plant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class PlantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Plant::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function plantRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'species' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'environment' => ['required', Rule::enum(PlantEnvironment::class)],
            'exposure' => ['required', Rule::enum(Exposure::class)],
            'watering_level' => ['required', Rule::enum(Level::class)],
            'maintenance_level' => ['required', Rule::enum(Level::class)],
            'adult_height_cm' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'adult_width_cm' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'price' => ['required', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('is_active')) {
            $this->merge(['is_active' => false]);
        }
    }
}
