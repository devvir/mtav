<?php

// Copilot - Pending review

namespace Tests\Unit\Services\Form\FormRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FormRequest with exists constraints using Rule objects.
 */
class CreateExistsTestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_id' => ['required', Rule::exists('projects', 'id')],
            'user_id'    => ['required', Rule::exists('users', 'id')],
        ];
    }
}
