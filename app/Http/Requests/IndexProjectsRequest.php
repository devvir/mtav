<?php

namespace App\Http\Requests;

use App\Models\User;

/**
 * @property-read string|null $q
 * @property-read bool|null $showAll
 *
 * @method User|null user($guard = null)
 */
class IndexProjectsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q'   => 'nullable|string|between:2,255',
            'all' => 'nullable|boolean',
        ];
    }
}
