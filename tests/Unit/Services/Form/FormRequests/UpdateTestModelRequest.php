<?php

// Copilot - Pending review

namespace Tests\Unit\Services\Form\FormRequests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update FormRequest with basic fields.
 */
class UpdateTestModelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'        => 'required|string|between:2,100',
            'description' => 'nullable|string',
        ];
    }
}
