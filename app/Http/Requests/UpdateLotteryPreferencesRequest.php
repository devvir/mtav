<?php

namespace App\Http\Requests;

/**
 * @property-read array<array{unit_id: int, order: int}> $preferences
 */
class UpdateLotteryPreferencesRequest extends FormRequest
{
    /**
     * This request is for Members only.
     */
    public function authorize(): bool
    {
        return $this->user()->isMember();
    }

    public function rules(): array
    {
        $validUnitIds = $this->user()->asMember()->family->unitType->units()->pluck('id');

        return [
            'preferences'      => 'required|array',
            'preferences.*.id' => 'required|in:' . $validUnitIds->join(','),
        ];
    }
}
