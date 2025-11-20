<?php

namespace App\Http\Requests;

/**
 * @property-read string|null $start_date
 * @property-read string $description
 */
class UpdateLotteryRequest extends FormRequest
{
    /**
     * This request is for Admins only.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'start_date'  => 'nullable|date|after:now',
            'description' => 'required|string|max:1000',
        ];
    }
}
