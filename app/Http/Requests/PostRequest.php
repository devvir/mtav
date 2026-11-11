<?php

namespace App\Http\Requests;

use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Http\FormRequest;

/** A post's words: a reply to a topic, or a post changed by whoever wrote it */
class PostRequest extends FormRequest
{
    public const BODY = ['required', 'string', 'max:' . Post::MAX_LENGTH];

    public function authorize(): bool
    {
        /** @var Post|null $post */
        $post = $this->route('post');
        /** @var Topic|null $topic */
        $topic = $this->route('topic');

        return $post ? $this->user()->can('update', $post) : $this->user()->can('reply', $topic);
    }

    public function rules(): array
    {
        return ['body' => self::BODY];
    }
}
