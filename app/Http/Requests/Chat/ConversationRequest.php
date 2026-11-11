<?php

namespace App\Http\Requests\Chat;

use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * A conversation of a project's chat, started with some of its people, or a group's name and
 * people added to it. Who may do it is asked first.
 */
class ConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->group();
        $group ? Gate::authorize('manageGroup', $group) : Gate::authorize('startChat', [Conversation::class, $this->project()]);

        return true;
    }

    public function rules(): array
    {
        return [
            // Started with someone at least; a group may only gain people
            'people'   => $this->group() ? ['array', 'max:100'] : ['required', 'array', 'min:1', 'max:100'],
            'people.*' => ['integer'],
            'title'    => ['nullable', 'string', 'max:80'],
        ];
    }

    private function project(): Project
    {
        return $this->route('project');
    }

    /** The group being changed (none while starting one) */
    private function group(): ?Conversation
    {
        return $this->route('conversation');
    }
}
