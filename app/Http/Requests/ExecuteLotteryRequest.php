<?php

namespace App\Http\Requests;

/**
 * @property-read bool|null $force
 */
class ExecuteLotteryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'override_mismatch' => ['sometimes', 'boolean'],
        ];
    }
}
