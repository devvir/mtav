<?php

namespace App\Http\Requests;

use App\Models\Admin;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Project::class, 'name')],
            'description' => ['required', 'string', 'max:65535'],
            'admins' => [
                'required',
                'array',
                Rule::exists(Admin::class, 'id')
                    // TODO : check if the where() is necessary, i.e. if it
                    //        applies Admin's globalScope automatically or not
                    ->where(fn ($query) => $query->where('is_admin', true)),
            ],
        ];
    }
}
