<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * @property-read string $firstname
 * @property-read string|null $lastname
 * @property-read string $email
 *
 * @method User|null user($guard = null)
 */
class UpdateAdminRequest extends FormRequest
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
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->route('admin')->id),
            ],
        ];
    }
}
