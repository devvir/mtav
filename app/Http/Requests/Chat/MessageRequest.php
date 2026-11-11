<?php

namespace App\Http\Requests\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * What a message says, written or reworded by whoever may: some words, short with the
 * Assistant. Only in this chat's conversations (a project's, or with none, the one outside any).
 */
class MessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $edited = $this->edited();
        if ($edited) {
            abort_unless($edited->conversation->project_id === $this->project()?->id, 404);
            Gate::authorize('editMessage', [Conversation::class, $edited]);
        } else {
            Gate::authorize('writeChat', $this->conversation());
            abort_unless($this->conversation()->project_id === $this->project()?->id, 404);
        }

        return true;
    }

    public function rules(): array
    {
        $max = $this->conversation()->is_assistant ? config('assistant.message_chars') : config('limits.chat_message_chars');

        return ['body' => ['required', 'string', "max:{$max}"]];
    }

    public function body(): string
    {
        return trim($this->validated('body'));
    }

    public function conversation(): Conversation
    {
        return $this->edited()?->conversation ?? $this->route('conversation');
    }

    /** The message being reworded (none while writing a new one) */
    private function edited(): ?Message
    {
        return $this->route('message');
    }

    private function project(): ?Project
    {
        return $this->route('project');
    }
}
