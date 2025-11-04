<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * @method User|null user($guard = null)
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
            'name' => ['required', 'string', 'max:255', 'unique:projects,name'],
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
