<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** An admin changing someone's place in the project: their family, and whether they are an admin */
class MembershipRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'family_id' => ['nullable', 'integer', Rule::exists(Family::class, 'id')->where('project_id', $project->id)],
            'is_admin'  => ['required', 'boolean'],
        ];
    }

    /** The family chosen (null: none). The form sends ids as text: compared as numbers, a role changed alone moves nobody */
    public function familyId(): ?int
    {
        $familyId = $this->validated('family_id');

        return isset($familyId) ? (int) $familyId : null;
    }

    public function asAdmin(): bool
    {
        return (bool) $this->validated('is_admin');
    }
}
