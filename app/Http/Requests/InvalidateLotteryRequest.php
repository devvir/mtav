<?php

namespace App\Http\Requests;

class InvalidateLotteryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin();
    }
}
