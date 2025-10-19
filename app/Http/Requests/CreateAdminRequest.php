<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CreateAdminRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project' => 'required|int|exists:'.Project::class.',id',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'firstname' => 'required|string|min:2|max:255',
            'lastname' => 'string|min:2|max:255',
        ];
    }
}
