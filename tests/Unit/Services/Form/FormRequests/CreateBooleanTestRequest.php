<?php

// Copilot - Pending review

namespace Tests\Unit\Services\Form\FormRequests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest with boolean field.
 */
class CreateBooleanTestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_active' => 'required|boolean',
            'is_admin'  => 'nullable|boolean',
        ];
    }
}
