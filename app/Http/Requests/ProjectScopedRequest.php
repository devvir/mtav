<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\User;

/**
 * @property-read int|null $project_id
 *
 * @method User|null user($guard = null)
 */
class ProjectScopedRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_id' => 'nullable|integer|exists:projects,id',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     * Ensures project context integrity and authorization:
     *  - 1. If current project is set and project_id is provided, they must match (context integrity)
     *  - 2. If project_id is provided, user must have access to that project (authorization)
     */
    public function authorize(): bool
    {
        $currentProject = Project::current();
        $requestedProjectId = $this->input('project_id');

        // Context integrity check: current project and requested project must match
        if ($currentProject && $requestedProjectId && (int) $requestedProjectId !== $currentProject->id) {
            return false;
        }

        // Authorization check: user must have access to requested project
        return ! $requestedProjectId || $this->userCanAccessProject($requestedProjectId);
    }

    /**
     * Prepare the data for validation.
     * Injects current project if no project_id is provided.
     */
    protected function prepareForValidation(): void
    {
        $currentProject = Project::current();

        // Inject current project if none provided
        if ($currentProject && ! $this->has('project_id')) {
            $this->merge(['project_id' => $currentProject->id]);
        }
    }

    /**
     * Check if the authenticated user can access the specified project.
     */
    protected function userCanAccessProject(int $projectId): bool
    {
        /** @var User $user */
        $user = $this->user();

        return $user->isSuperadmin()
            || ($user->asMember()?->project?->id === $projectId)
            || $user->asAdmin()?->manages($projectId);
    }
}
