<?php

namespace App\Http\Requests;

/**
 * @property-read string $polygon
 * @property-read float $width
 * @property-read float $height
 * @property-read string $unit_system
 */
class UpdatePlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'polygon'     => 'required|json',
            'width'       => 'required|numeric|min:1',
            'height'      => 'required|numeric|min:1',
            'unit_system' => 'required|string|in:meters,feet',
        ];
    }
}
