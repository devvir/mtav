<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\User;

/**
 * @property-read Array<int> $project_ids
 * @property-read string $email
 * @property-read string $firstname
 * @property-read string|null $lastname
 *
 * @method User|null user($guard = null)
 */
class CreateAdminRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_ids' => ['required', 'array'],
            'project_ids.*' => ['int', 'exists:projects,id'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'firstname' => ['required', 'string', 'min:2', 'max:255'],
            'lastname' => ['string', 'min:2', 'max:255'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Regular admins can only assign available Projects (i.e. those they manage)
            $managedProjectIds = Project::pluck('id')->toArray();
            $requestedProjectIds = $this->input('project_ids', []);
            $unauthorizedProjects = array_diff($requestedProjectIds, $managedProjectIds);

            if ($unauthorizedProjects) {
                $validator->errors()->add(
                    'project_ids',
                    __('validation.unauthorized_projects_assignment', [
                        'ids' => implode(', ', $unauthorizedProjects),
                    ])
                );
            }
        });
    }
}
