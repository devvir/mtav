<?php

// Copilot - Pending review

namespace Tests\Unit\Services\Form\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest with array fields and wildcard rules.
 */
class CreateArrayTestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tags'       => 'required|array',
            'tags.*'     => 'string|max:50',
            'user_ids'   => 'required|array',
            'user_ids.*' => Rule::exists('users', 'id'),
        ];
    }
}
