<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

class StorePlantRequest extends PlantRequest
{
    public function rules(): array
    {
        return $this->plantRules();
    }
}
