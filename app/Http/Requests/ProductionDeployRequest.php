<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A release from main: to production unless another released environment is named, at a commit if one is */
class ProductionDeployRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'environment' => ['nullable', 'string', Rule::in(config('delivery.released'))],
            'commit'      => ['nullable', 'string', 'regex:/^[0-9a-f]{7,40}$/'],
        ];
    }

    public function environment(): string
    {
        return $this->validated('environment') ?? 'production';
    }

    public function commit(): string
    {
        return $this->validated('commit') ?? '';
    }
}
