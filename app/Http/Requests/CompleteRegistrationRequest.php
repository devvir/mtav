<?php

// Copilot - pending review

namespace App\Http\Requests;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rules\Password;

class CompleteRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'legal_id' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048', // 2MB max
        ];
    }
}
