<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @method User|null user($guard = null)
 *
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:1000'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()),
            ],
        ];
    }
}
