<?php

namespace App\Http\Requests;

/**
 * @property-read string $name
 * @property-read string|null $description
 * @property-read int|null $project_id
 */
class CreateUnitTypeRequest extends ProjectScopedRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|integer|exists:projects,id',
        ];
    }
}
