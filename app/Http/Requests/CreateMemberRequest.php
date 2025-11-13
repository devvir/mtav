<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Rules\BelongsToProject;

/**
 * @property-read int $project_id
 * @property-read int $family_id
 * @property-read string $email
 * @property-read string $firstname
 * @property-read string|null $lastname
 */
class CreateMemberRequest extends ProjectScopedRequest
{
    public function rules(): array
    {
        if ($this->user()->isMember()) {
            $this->merge(['family_id' => $this->user()->family_id]);
        }

        return [
            'project_id' => 'required|exists:projects,id',
            'family_id'  => ['required', new BelongsToProject(
                Family::class,
                $this->project_id,
                'validation.family_belongs_to_project'
            )],
            'email'     => 'required|email|max:255|unique:users,email',
            'firstname' => 'required|string|between:2,80',
            'lastname'  => 'nullable|string|between:2,80',
        ];
    }
}
