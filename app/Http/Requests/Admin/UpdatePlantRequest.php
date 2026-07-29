<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Plant;

class UpdatePlantRequest extends PlantRequest
{
    public function authorize(): bool
    {
        $plant = $this->route('plant');

        return $plant instanceof Plant
            ? $this->user()?->can('update', $plant) ?? false
            : false;
    }

    public function rules(): array
    {
        return $this->plantRules();
    }
}
