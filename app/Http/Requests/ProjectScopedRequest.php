<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\User;

/**
 * This request may be used in two scenarios:
 *   - directly when a Project is optional (e.g. create Member -- works global or in-project)
 *   - as base, for Requests that have further specs (e.g. create Event)
 *     ~ e.g. children Request makes project_id required
 *     ~ e.g. children Request has other attributes to validate
 *
 * For requests expecting an optional project_id:
 *   - if a current Project is set, reject different project_id in the request (403)
 *   - if no current Project, and no project_id is given, move on
 *   - if no current Project, and a project_id is provided, validate user access to it
 *
 * Note: if a current Project is set, its project_id will be injected in the Request
 *
 * @property-read int|null $project_id
 *
 * @method User|null user($guard = null)
 */
class ProjectScopedRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_id' => 'nullable|exists:projects,id',
        ];
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
     * Determine if the user is authorized (context integrity and authorization):
     *  - 1. If project is set and project_id is provided, they must match (context integrity)
     *  - 2. If project_id is provided, user must have access to that project (authorization)
     */
    public function authorize(): bool
    {
        $currentProject = Project::current();
        $requestedProjectId = $this->input('project_id');

        // Context integrity check: current project and requested project must match
        if ($currentProject && (int) $requestedProjectId !== $currentProject->id) {
            return false;
        }

        // Authorization check: user must have access to requested project
        return ! $requestedProjectId || $this->userCanAccessProject($requestedProjectId);
    }

    /**
     * Check if the authenticated user can access the specified project.
     */
    protected function userCanAccessProject(int $projectId): bool
    {
        $user = $this->user();

        return $user->isSuperadmin()
            || ($user->asMember()?->project?->id === $projectId)
            || $user->asAdmin()?->manages($projectId);
    }
}
