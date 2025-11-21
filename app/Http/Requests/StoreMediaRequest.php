<?php

namespace App\Http\Requests;

use Illuminate\Http\UploadedFile;

/**
 * @property-read array<UploadedFile> $files
 * @property-read string $description
 */
class StoreMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => 'required|string|max:500',
            'files'       => 'required|array|min:1',
            'files.*'     => [
                'file',
                'max:102400', // 100MB in kilobytes
                'mimes:jpeg,jpg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,wav,mp4,avi,mov,webm',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => __('validation.media_description_required'),
            'description.max'      => __('validation.media_description_too_long'),
            'files.required'       => __('validation.media_file_required'),
            'files.min'            => __('validation.media_file_required'),
            'files.*.max'          => __('validation.media_file_too_large'),
            'files.*.mimes'        => __('validation.media_invalid_file_type'),
        ];
    }
}
