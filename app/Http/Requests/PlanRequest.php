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
    public const ITEM_TYPES = ['unit', 'building', 'street', 'green', 'common', 'parking', 'playground', 'entrance', 'other'];

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'width'       => ['required', 'numeric', 'between:100,10000'],
            'height'      => ['required', 'numeric', 'between:100,10000'],
            'polygon'     => ['required', 'array', 'min:3', 'max:200'],
            'polygon.*'   => ['required', 'array', 'size:2'],
            'polygon.*.*' => ['required', 'numeric'],
            'items'       => ['present', 'array', 'max:2000'],
            // An id the plan no longer has (a shape removed, then brought back by undo) makes a
            // new shape: the editor's history must never make the plan unsavable
            'items.*.id'          => ['nullable', 'integer'],
            'items.*.key'         => ['nullable', 'string', 'max:40'],
            'items.*.type'        => ['required', Rule::in(self::ITEM_TYPES)],
            'items.*.floor'       => ['required', 'integer', 'between:-5,100'],
            'items.*.placed'      => ['boolean'],
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

            // Units come and go with the units themselves: a shape is a unit only if it is one
            // already (units the editor left out, added meanwhile, stay as they are)
            $unitItems = $project->plan->items()->whereNotNull('unit_id')->pluck('id');
            $claimed = collect($this->input('items'))->where('type', 'unit')->pluck('id');

            if ($claimed->contains(fn ($id) => ! $unitItems->contains($id))) {
                $validator->errors()->add('items', __('validation.plan_unit_items'));
            }
        }];
    }
}
