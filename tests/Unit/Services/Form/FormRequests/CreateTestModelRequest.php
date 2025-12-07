<?php

// Copilot - Pending review

namespace Tests\Unit\Services\Form\FormRequests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Simple FormRequest with basic string fields for testing.
 */
class CreateTestModelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'  => 'required|string|between:2,255',
            'email' => 'required|email',
            'age'   => 'nullable|integer|min:1|max:120',
        ];
    }
}
