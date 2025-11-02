<?php

namespace App\Http\Requests;

/**
 * @property-read int|null $project_id
 * @property-read string|null $q
 */
class FilteredIndexRequest extends ProjectScopedRequest
{
    public function rules(): array
    {
        return [
            'project_id' => 'nullable|integer|exists:projects,id',
            'q' => 'nullable|string|max:255',
        ];
    }
}
