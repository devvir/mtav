<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Validation\Rule;

/**
 * @property-read string $type
 * @property-read string $title
 * @property-read string $description
 * @property-read string|null $location
 * @property-read string|null $start_date
 * @property-read string|null $end_date
 * @property-read bool $is_published
 */
class CreateEventRequest extends FormRequest
{
    public function rules(): array
    {
        $isOnlineEvent = ($this->input('type') === EventType::ONLINE->value);

        return [
            'type'         => ['required', Rule::enum(EventType::class)->except(EventType::LOTTERY)],
            'title'        => 'required|string|max:255',
            'description'  => 'required|string|between:20,500',
            'location'     => ['nullable', 'max:500', $isOnlineEvent ? 'url' : 'string'],
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after:start_date',
            'is_published' => 'boolean',
        ];
    }
}
