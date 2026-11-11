<?php

namespace App\Http\Requests;

use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;

/** A topic opened: its title and its first post */
class TopicRequest extends FormRequest
{
    public const TITLE = ['required', 'string', 'max:160'];

    public function authorize(): bool
    {
        return $this->user()->can('create', [Topic::class, $this->route('project')]);
    }

    public function rules(): array
    {
        return ['title' => self::TITLE, 'body' => PostRequest::BODY];
    }
}
