<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The whole plan, as drawn: the boundary, its size, and every shape. Shapes without an
 * id are new; shapes of the plan missing from the list are removed (units' shapes
 * cannot be: they live and die with their unit).
 */
class PlanRequest extends FormRequest
{
    public const ITEM_TYPES = ['unit', 'building', 'street', 'green', 'common', 'other'];

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'width'               => ['required', 'numeric', 'between:100,10000'],
            'height'              => ['required', 'numeric', 'between:100,10000'],
            'polygon'             => ['required', 'array', 'min:3', 'max:200'],
            'polygon.*'           => ['required', 'array', 'size:2'],
            'polygon.*.*'         => ['required', 'numeric'],
            'items'               => ['present', 'array', 'max:2000'],
            'items.*.id'          => ['nullable', 'integer', Rule::exists('plan_items', 'id')->where('plan_id', $project->plan->id)],
            'items.*.type'        => ['required', Rule::in(self::ITEM_TYPES)],
            'items.*.floor'       => ['required', 'integer', 'between:-5,100'],
            'items.*.name'        => ['nullable', 'string', 'max:80'],
            'items.*.color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'items.*.polygon'     => ['required', 'array', 'min:3', 'max:200'],
            'items.*.polygon.*'   => ['required', 'array', 'size:2'],
            'items.*.polygon.*.*' => ['required', 'numeric'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Project $project */
            $project = $this->route('project');

            $kept = collect($this->input('items'))->pluck('id')->filter();
            $unitItems = $project->plan->items()->whereNotNull('unit_id')->pluck('id');

            if ($unitItems->diff($kept)->isNotEmpty()) {
                $validator->errors()->add('items', __('validation.plan_unit_items'));
            }

            $newUnits = collect($this->input('items'))->whereNull('id')->where('type', 'unit');

            if ($newUnits->isNotEmpty()) {
                $validator->errors()->add('items', __('validation.plan_unit_items'));
            }
        }];
    }
}
