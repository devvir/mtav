<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

/**
 * @property-read array $polygon
 * @property-read array $items
 */
class UpdatePlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'polygon'             => ['required', 'array', 'min:3'],
            'polygon.*'           => ['required', 'array', 'size:2'],
            'polygon.*.*'         => ['required', 'numeric'],
            'items'               => ['present', 'array'],
            'items.*.id'          => ['required', 'integer', 'exists:plan_items,id'],
            'items.*.polygon'     => ['required', 'array', 'min:3'],
            'items.*.polygon.*'   => ['required', 'array', 'size:2'],
            'items.*.polygon.*.*' => ['required', 'numeric'],
        ];
    }

    /**
     * Make sure all provided item ids belong to the plan being updated.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $itemIds = $this->route('plan')->items()->pluck('id');
            $invalid = collect($this->items)->pluck('id')->diff($itemIds);

            if ($invalid->isNotEmpty()) {
                $validator->errors()->add('items', __('validation.plan_items_mismatch'));
            }
        });
    }
}
