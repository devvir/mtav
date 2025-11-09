<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read string $description
 * @property-read string $organization
 * @property-read array<int> $admins
 */
class CreateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:projects,name',
            'description' => 'required|string|between:20,500',
            'organization' => 'required|string|between:2,255',
            'admins' => 'required|array',
            'admins.*' => [
                'required',
                Rule::exists('users', 'id')->where('is_admin', true),
            ],
        ];
    }
}
