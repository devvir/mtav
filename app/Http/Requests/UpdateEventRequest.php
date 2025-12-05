<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends CreateEventRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            /** Lottery will not provide any of these fields, therefore they're optional for edits */
            'type'         => ['sometimes', 'required', Rule::enum(EventType::class)->except(EventType::LOTTERY)],
            'title'        => 'sometimes|required|string|max:255',
            'end_date'     => 'sometimes|nullable|date|after:start_date',
            'is_published' => 'sometimes|boolean',
        ]);
    }
}
