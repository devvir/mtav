<?php

namespace App\Http\Requests\Chat;

use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * The chat's window, asking for all it shows: with a conversation open (`conversation`) when
 * it names one of this chat's that is theirs to read.
 */
class PanelRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function asked(): ?Conversation
    {
        if (! $this->integer('conversation')) {
            return null;
        }

        /** @var Project|null $project */
        $project = $this->route('project');
        $conversation = Conversation::where('project_id', $project?->id)->findOrFail($this->integer('conversation'));
        Gate::authorize('readChat', $conversation);

        return $conversation;
    }
}
