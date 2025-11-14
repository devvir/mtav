<?php

namespace App\Http\Requests;

/**
 * @property-read \Illuminate\Http\UploadedFile $file
 * @property-read string $description
 */
class StoreMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                'mimes:jpeg,jpg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,rar',
            ],
            'description' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required'        => __('validation.media_file_required'),
            'file.max'             => __('validation.media_file_too_large'),
            'file.mimes'           => __('validation.media_invalid_file_type'),
            'description.required' => __('validation.media_description_required'),
            'description.max'      => __('validation.media_description_too_long'),
        ];
    }
}
