<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @property-read int[] $project_ids
 * @property-read string $email
 * @property-read string $firstname
 * @property-read string|null $lastname
 *
 * @method User|null user($guard = null)
 *
 * @mixin Request
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:user,email'],
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
            /** @var User $user */
            $user = $this->user();

            // Super admins can assign any projects
            if ($user->isSuperAdmin()) {
                return;
            }

            // Regular admins can only assign projects they manage
            $managedProjectIds = $user->projects()->pluck('projects.id')->toArray();
            $requestedProjectIds = $this->input('project_ids', []);
            $unauthorizedProjects = array_diff($requestedProjectIds, $managedProjectIds);

            if (! empty($unauthorizedProjects)) {
                $validator->errors()->add(
                    'project_ids',
                    __('You can only assign projects you manage. Unauthorized projects: :ids', [
                        'ids' => implode(', ', $unauthorizedProjects),
                    ])
                );
            }
        });
    }
}
