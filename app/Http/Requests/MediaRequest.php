<?php

namespace App\Http\Requests;

use App\Enums\MediaCategory;
use App\Enums\ProjectFeature;
use App\Models\Project;
use App\Services\MediaService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Sharing files: types are checked by content, not by name, sizes by the project's
 * limits, and images by their pixels too (a small file can claim too many to open).
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
                function (string $attribute, mixed $file, Closure $fail) use ($project) {
                    if ($file instanceof UploadedFile && MediaService::tooManyPixels($file->getPathname())) {
                        $fail(__('validation.image_too_large', ['max' => MediaService::MAX_MEGAPIXELS]));
                    }
                    // A gallery or files turned off takes nothing in
                    if ($file instanceof UploadedFile && ($category = MediaService::categoryOf((string) $file->getMimeType()))) {
                        $feature = in_array($category, [MediaCategory::IMAGE, MediaCategory::VIDEO], true) ? ProjectFeature::GALLERY : ProjectFeature::FILES;
                        ! $project->hasFeature($feature) && $fail(__('validation.feature_off'));
                    }
                },
            ],
        ];
    }
}
