<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Request;

class UserRegistration
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public User $user,
        public string $token,
        public ?string $appUrl = null
    ) {
        // Capture the current request URL if not provided
        $this->appUrl ??= Request::getSchemeAndHttpHost();
    }
}
