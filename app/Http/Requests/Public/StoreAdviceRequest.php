<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Enums\Exposure;
use App\Enums\Level;
use App\Enums\PlantEnvironment;
use App\Enums\SpaceSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'environment' => [
                'required',
                Rule::in([PlantEnvironment::INDOOR->value, PlantEnvironment::OUTDOOR->value]),
            ],
            'exposure' => ['required', Rule::enum(Exposure::class)],
            'space_size' => ['required', Rule::enum(SpaceSize::class)],
            'maintenance_availability' => ['required', Rule::enum(Level::class)],
            'free_text_description' => ['required', 'string', 'min:20', 'max:5000'],
            'consent' => ['accepted'],
        ];
    }
}
