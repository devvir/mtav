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

    public const MAX_ITEMS = 2000;

    public const MAX_CORNERS = 200;

    /**
     * How many first: a rule on each shape or point would read every one sent, however many.
     * Points are plain [x, y] lists (an {x, y} object is no point the plan can be read by).
     */
    protected function prepareForValidation(): void
    {
        validator($this->all(), [
            'polygon' => ['required', 'list', 'min:3', 'max:' . self::MAX_CORNERS],
            'items'   => ['present', 'list', 'max:' . self::MAX_ITEMS],
        ])->validate();

        validator($this->all(), ['items.*.polygon' => ['required', 'list', 'min:3', 'max:' . self::MAX_CORNERS]])->validate();
    }

    public function rules(): array
    {
        return [
            'width'       => ['required', 'numeric', 'between:100,10000'],
            'height'      => ['required', 'numeric', 'between:100,10000'],
            'polygon'     => ['required', 'list', 'min:3', 'max:' . self::MAX_CORNERS],
            'polygon.*'   => ['required', 'list', 'size:2'],
            'polygon.*.*' => ['required', 'numeric'],
            'items'       => ['present', 'list', 'max:' . self::MAX_ITEMS],
            // An id the plan no longer has (a shape removed, then brought back by undo) makes a
            // new shape: the editor's history must never make the plan unsavable
            'items.*.id'          => ['nullable', 'integer'],
            'items.*.key'         => ['nullable', 'string', 'max:40'],
            'items.*.type'        => ['required', Rule::in(self::ITEM_TYPES)],
            'items.*.floor'       => ['required', 'integer', 'between:-5,100'],
            'items.*.placed'      => ['boolean'],
            'items.*.name'        => ['nullable', 'string', 'max:80'],
            'items.*.color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'items.*.polygon'     => ['required', 'list', 'min:3', 'max:' . self::MAX_CORNERS],
            'items.*.polygon.*'   => ['required', 'list', 'size:2'],
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
        },
        ];
    }
}
