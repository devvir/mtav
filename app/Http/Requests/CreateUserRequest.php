<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project' => 'required|int|exists:' . Project::class . ',id',
            'family' => 'required|int|exists:' . Family::class . ',id',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'firstname' => 'required|string|max:255',
            'lastname' => 'string|max:255',
        ];
    }
}
