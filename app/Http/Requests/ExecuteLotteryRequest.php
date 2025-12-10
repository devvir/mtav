<?php

namespace App\Http\Requests;

/**
 * @property-read array<string>|null $options
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
            'options'   => 'sometimes|array',
            'options.*' => 'string',
        ];
    }
}
