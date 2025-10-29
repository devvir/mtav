<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\OverlappingProjectsConstraint;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @property-read string $firstname
 * @property-read string|null $lastname
 * @property-read string $email
 *
 * @method User|null user($guard = null)
 *
 * @mixin Request
 */
class UpdateMemberRequest extends FormRequest
{
    use OverlappingProjectsConstraint;

    /**
     * Throws 403 if member exists but user doesn't have access.
     *
     * - Members can update themselves
     * - Admins can only update members in projects they manage
     *
     * @see MemberPolicy@update on why this access policy lives here.
     */
    protected function prepareForValidation(): void
    {
        $this->validateOverlap(
            $this->user(),
            $this->route('member'),
            __('You can only update members from projects you have access to.')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'between:2,80'],
            'lastname' => ['nullable', 'string', 'between:2,80'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->route('member')->id),
            ],
        ];
    }
}
