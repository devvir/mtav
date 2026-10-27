<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'polygon'             => ['required', 'array', 'min:3'],
            'polygon.*'           => ['required', 'array', 'size:2'],
            'polygon.*.*'         => ['required', 'numeric'],
            'items'               => ['present', 'array'],
            'items.*.id'          => ['required', 'integer', Rule::exists('plan_items', 'id')->where('plan_id', $project->plan->id)],
            'items.*.polygon'     => ['required', 'array', 'min:3'],
            'items.*.polygon.*'   => ['required', 'array', 'size:2'],
            'items.*.polygon.*.*' => ['required', 'numeric'],
        ];
    }
}
