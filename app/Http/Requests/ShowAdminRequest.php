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
class ShowAdminRequest extends FormRequest
{
    use OverlappingProjectsConstraint;

    /**
     * Throws 403 if admin exists but user doesn't have access.
     *
     * - Members can see admins who manage their project
     * - Admins can only see admins with overlapping managed projects
     *
     * @see AdminPolicy@view on why this access policy lives here.
     */
    protected function prepareForValidation(): void
    {
        $this->validateOverlap(
            $this->user(),
            $this->route('admin'),
            __('You can only view admins from projects you have access to.')
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
