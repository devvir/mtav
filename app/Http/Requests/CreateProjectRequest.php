<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Exists;

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
            'name'        => ['required', 'string', 'max:255', 'unique:projects,name'],
            'description' => ['required', 'string', 'max:65535'],
            'admins'      => ['required', 'array', new Exists('users', 'id')->where(fn ($query) => $query->where('is_admin', true))],
        ];
    }
}
