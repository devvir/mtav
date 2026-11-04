<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Support\Variant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProjectRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Project|null $project */
        $project = $this->route('project');

        return [
            'name'          => ['required', 'string', 'between:2,120'],
            'description'   => ['required', 'string', 'between:2,2000'],
            'organization'  => ['nullable', 'string', 'between:2,120'],
            'listed'        => ['sometimes', 'boolean'],
            'open_to_join'  => ['sometimes', 'boolean'],
            'allows_guests' => ['sometimes', 'boolean'],
            // Only what this variant lets a project choose
            'feature_choices'   => ['sometimes', 'array:' . implode(',', array_keys(Variant::choices()))],
            'feature_choices.*' => ['boolean'],
        ];
    }

    /** Guests go only once there are none, and none on their way: no rule made false by its data */
    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Project|null $project */
            $project = $this->route('project');

            if (! $project?->allows_guests || $this->boolean('allows_guests', true)) {
                return;
            }

            $guests = $project->memberships()->whereNull('family_id')->where('is_admin', false)->count()
                + $project->invitations()->pending()->whereNull('family_id')->where('is_admin', false)->count();

            if ($guests) {
                $validator->errors()->add('allows_guests', __('validation.guests_remain', ['count' => $guests]));
            }
        },
        ];
    }
}
