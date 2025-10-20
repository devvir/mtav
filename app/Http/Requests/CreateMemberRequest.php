<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMemberRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project' => ['required', Rule::exists(Project::class, 'id')],
            'family' => ['required', Rule::exists(Family::class, 'id')],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'firstname' => ['required', 'string', 'min:2', 'max:255'],
            'lastname' => ['nullable', 'string', 'min:2', 'max:255'],
        ];
    }
}
