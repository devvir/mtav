<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Services\MediaService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Sharing files: types are checked by content, not by name, and sizes by the
 * project's limits.
 */
class MediaRequest extends FormRequest
{
    public const MAX_FILES = 20;

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');
        $updating = (bool) $this->route('media');

        return [
            'description' => ['required', 'string', 'between:2,255'],
            'alt_text'    => ['nullable', 'string', 'max:255'],
            'files'       => [$updating ? 'prohibited' : 'required', 'array', 'min:1', 'max:' . self::MAX_FILES],
            'files.*'     => [
                'file',
                'max:' . MediaService::maxFileKb($project),
                'mimetypes:' . implode(',', array_merge(...array_values(MediaService::MIME_TYPES))),
            ],
        ];
    }
}
