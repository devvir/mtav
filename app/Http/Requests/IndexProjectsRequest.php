<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Http\Requests\FormRequest;

/**
 * @property-read string|null $q
 * @property-read bool|null $showAll
 *
 * @method User|null user($guard = null)
 *
 */
class IndexProjectsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:255',
            'showAll' => 'nullable|boolean',
        ];
    }
}
