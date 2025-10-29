<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * @method User|null user($guard = null)
 *
 * @mixin Request
 */
class EditMemberRequest extends UpdateMemberRequest
{
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
