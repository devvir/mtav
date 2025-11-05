<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Rules\BelongsToProject;

/**
 * @property-read int $project_id
 * @property-read int $family
 * @property-read string $email
 * @property-read string $firstname
 * @property-read string|null $lastname
 */
class CreateMemberRequest extends ProjectScopedRequest
{
    public function rules(): array
    {
        $projectId = $this['project_id'];

        if ($this->user()->isMember()) {
            $this->merge(['family_id' => $this->user()->family_id]);
        }

        return [
            'project_id' => 'required|integer|exists:projects,id',
            'family_id' => array_filter([
                'required',
                'exists:families,id',
                $projectId ? new BelongsToProject(Family::class, $projectId, 'validation.family_belongs_to_project') : null,
            ]),
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'firstname' => ['required', 'string', 'between:2,80'],
            'lastname' => ['nullable', 'string', 'between:2,80'],
        ];
    }
}
