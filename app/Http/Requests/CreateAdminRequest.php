<?php

namespace App\Http\Requests;

use App\Models\Project;

/**
 * @property-read array<int> $project_ids
 * @property-read string $email
 * @property-read string $firstname
 * @property-read string|null $lastname
 */
class CreateAdminRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_ids'   => 'required|array',
            'project_ids.*' => 'exists:projects,id',
            'email'         => 'required|email|max:255|unique:users,email',
            'firstname'     => 'required|string|between:2,80',
            'lastname'      => 'nullable|string|between:2,80',
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
