<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read string      $email
 * @property-read string      $firstname
 * @property-read string|null $lastname
 * @property-read string|null $legal_id
 * @property-read string|null $phone
 * @property-read string|null $about
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
            'legal_id' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
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
