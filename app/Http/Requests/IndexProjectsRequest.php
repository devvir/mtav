<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * @property-read string|null $q
 * @property-read bool|null $showAll
 *
 * @method User|null user($guard = null)
 *
 * @mixin Request
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
