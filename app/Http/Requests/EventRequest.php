<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type'         => ['required', Rule::enum(EventType::class)->except(EventType::LOTTERY)],
            'title'        => ['required', 'string', 'between:2,160'],
            'description'  => ['required', 'string', 'between:2,5000'],
            'location'     => ['nullable', 'string', 'max:255', Rule::when($this->input('type') === EventType::ONLINE->value, 'url:http,https')],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date', 'after:start_date'],
            'is_published' => ['boolean'],
            'allows_rsvp'  => ['boolean'],
        ];
    }
}
