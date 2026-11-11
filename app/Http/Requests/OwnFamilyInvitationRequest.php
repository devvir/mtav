<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Someone of a family inviting the rest of it into the project: always into their own family
 * (an admin in none invites from the project's settings, choosing the family).
 */
class OwnFamilyInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $family = $this->family();

        return $family && $this->user()->can('invite', [$this->project(), $family]);
    }

    public function rules(): array
    {
        return PersonRules::invitee();
    }

    public function family(): ?Family
    {
        return $this->user()->familyIn($this->project());
    }

    private function project(): Project
    {
        return $this->route('project');
    }
}
