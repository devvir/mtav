<?php

namespace App\Http\Requests;

use App\Models\Admin;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @method User|null user($guard = null)
 *
 * @mixin Request
 */
class CreateProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:project,name'],
            'description' => ['required', 'string', 'max:65535'],
            'organization' => ['required', 'string', 'max:255'],
            'admins' => ['required', 'array'],
            'admins.*' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('is_admin', true),
            ],
        ];
    }
}
