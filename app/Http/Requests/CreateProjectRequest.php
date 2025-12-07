<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * @property-read string $name
 * @property-read string $description
 * @property-read string|null $organization
 * @property-read array<int> $admins
 */
class CreateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => 'required|string|between:2,255|unique:projects,name',
            'description'  => 'required|string|between:2,500',
            'organization' => 'nullable|string|between:2,255',

            /** Pick one or more existing admins, and/or invite a new Admin */
            'new_admin_email'     => 'required_with:new_admin_firstname|nullable|email|between:2,255|unique:users,email',
            'new_admin_firstname' => 'required_with:new_admin_email|nullable|string|between:2,80',
            'new_admin_lastname'  => 'nullable|string|between:2,80',
            'admins'              => 'nullable|array',
            'admins.*'            => [ Rule::exists('users', 'id')->where('is_admin', true) ],
        ];
    }
}
