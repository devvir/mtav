<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** A topic changed: renamed (by its writer or admins), pinned or closed (by admins) */
class TopicChangeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title'     => ['sometimes', ...TopicRequest::TITLE],
            'is_pinned' => ['sometimes', 'boolean'],
            'is_closed' => ['sometimes', 'boolean'],
        ];
    }

    /** Only the title: what its writer may change, as admins may */
    public function renamesOnly(): bool
    {
        return array_keys($this->validated()) === ['title'];
    }
}
