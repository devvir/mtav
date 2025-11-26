<?php

// Copilot - Pending review

namespace Tests\Unit\Services\Form\FormRequests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest with 'in' constraint.
 */
class CreateInRuleTestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status'   => 'required|in:draft,published,archived',
            'priority' => 'nullable|in:low,medium,high',
        ];
    }
}
