<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\OverlappingProjectsConstraint;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * @method User|null user($guard = null)
 *
 * @mixin Request
 */
class DeleteMemberRequest extends FormRequest
{
    use OverlappingProjectsConstraint;

    /**
     * Throws 403 if member exists but user doesn't have access.
     *
     * - Members can delete themselves
     * - Admins can only delete members in projects they manage
     *
     * @see MemberPolicy@delete on why this access policy lives here.
     */
    protected function prepareForValidation(): void
    {
        $this->validateOverlap(
            $this->user(),
            $this->route('member'),
            __('You can only delete members from projects you have access to.')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
