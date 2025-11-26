<?php

// Copilot - Pending review

namespace Tests\Unit\Services\Form\FormRequests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Complex FormRequest with various field types.
 */
class CreateMixedTestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0|max:999999.99',
            'quantity'     => 'required|integer|between:1,1000',
            'is_featured'  => 'boolean',
            'category'     => 'required|in:electronics,books,clothing',
            'event_type'   => ['required', new Enum(EventType::class)],
            'project_id'   => ['required', Rule::exists('projects', 'id')],
            'published_at' => 'nullable|date',
        ];
    }
}
