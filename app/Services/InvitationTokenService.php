<?php

// Copilot - pending review

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class InvitationTokenService
{
    /**
     * Generate a secure invitation token.
     *
     * @return array{token: string, hashed: string}
     */
    public static function generate(): array
    {
        $token = base64_encode(random_bytes(32));

        return [
            'token' => $token,
            'hashed' => Hash::make($token),
        ];
    }

    /**
     * Verify a token against a hashed password.
     */
    public static function verify(string $token, string $hashedPassword): bool
    {
        return Hash::check($token, $hashedPassword);
    }
}
