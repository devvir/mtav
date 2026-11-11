<?php

namespace App\Http\Requests;

use App\Models\PushSubscription;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/** A browser's address for notifications, at a push service, with the keys to write to it */
class PushSubscriptionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'url:https', 'max:700', function (string $attribute, mixed $endpoint, Closure $fail) {
                PushSubscription::isServiceAddress((string) $endpoint) || $fail(__('validation.push_device'));
            },
            ],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth'   => ['required', 'string', 'max:255', function (string $attribute, mixed $auth, Closure $fail) {
                PushSubscription::areKeys((string) $this->input('keys.p256dh'), (string) $auth) || $fail(__('validation.push_device'));
            },
            ],
        ];
    }
}
