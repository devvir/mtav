<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An admin inviting someone into a family of the project, as an admin, or both.
 */
class InvitationRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'email'     => ['required', 'email', 'max:255'],
            'firstname' => ['nullable', 'string', 'between:2,80'],
            'lastname'  => ['nullable', 'string', 'between:2,80'],
            'is_admin'  => ['boolean'],
            'family_id' => [
                // Guests come with neither a family nor a role
                ...($project->allows_guests ? [] : ['required_unless:is_admin,true,1']),
                'nullable',
                Rule::exists(Family::class, 'id')->where('project_id', $project->id),
            ],
        ];
    }

    public function family(): ?Family
    {
        return Family::find($this->validated('family_id'));
    }

    public function asAdmin(): bool
    {
        return $this->boolean('is_admin');
    }
}
