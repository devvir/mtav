<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowInvitationRequest extends FormRequest
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
            'email' => 'sometimes|required|email|exists:users,email',
            'token' => 'sometimes|required|string',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('Invalid invitation link, please contact an administrator.'),
            'email.email' => __('Invalid invitation link, please contact an administrator.'),
            'email.exists' => __('Invalid invitation link, please contact an administrator.'),
            'token.required' => __('Invalid invitation link, please contact an administrator.'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new \Illuminate\Validation\ValidationException(
            $validator,
            redirect()->route('login')
                ->with('error', __('Invalid invitation link, please contact an administrator.'))
        );
    }
}
