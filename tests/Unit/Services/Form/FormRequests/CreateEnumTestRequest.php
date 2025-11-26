<?php

// Copilot - Pending review

namespace Tests\Unit\Services\Form\FormRequests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * FormRequest with enum field.
 */
class CreateEnumTestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'event_type' => ['required', new Enum(EventType::class)],
            'name'       => 'required|string',
        ];
    }
}
