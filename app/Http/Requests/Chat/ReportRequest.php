<?php

namespace App\Http\Requests\Chat;

use App\Models\Message;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Sending a report one of the Assistant's buttons offers: an answer of the Assistant, in this
 * chat, in a conversation theirs to read. Which button, by its place among them (`action`).
 */
class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Message $message */
        $message = $this->route('message');
        $conversation = $message->conversation;
        /** @var Project|null $project */
        $project = $this->route('project');
        abort_unless($message->from_assistant && $conversation->is_assistant && $conversation->project_id === $project?->id, 404);
        Gate::authorize('readChat', $conversation);

        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function action(): int
    {
        return $this->integer('action');
    }
}
