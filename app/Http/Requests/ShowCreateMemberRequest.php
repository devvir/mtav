<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;

/**
 * @property-read int|null $project_id
 *
 * @mixin Request
 */
class ShowCreateMemberRequest extends ProjectScopedRequest
{
    public function rules(): array
    {
        return [
            'project_id' => 'nullable|integer|exists:projects,id',
        ];
    }
}
