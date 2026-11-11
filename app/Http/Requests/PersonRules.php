<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

/**
 * What a person gives about themselves, or what whoever invites them gives, checked the same
 * wherever it is asked: a name, an address, a password.
 */
final class PersonRules
{
    private const NAME = ['string', 'between:2,80'];

    /** @return array<string, list<mixed>>  Their own name: the first one, at least */
    public static function name(): array
    {
        return [
            'firstname' => ['required', ...self::NAME],
            'lastname'  => ['nullable', ...self::NAME],
        ];
    }

    /** @return array<string, list<mixed>>  Opening an account: their name and a password */
    public static function account(): array
    {
        return [...self::name(), 'password' => self::newPassword()];
    }

    /** @return array<string, list<mixed>>  Someone invited: an address, and their name if known */
    public static function invitee(): array
    {
        return [
            'email'     => self::email(),
            'firstname' => ['nullable', ...self::NAME],
            'lastname'  => ['nullable', ...self::NAME],
        ];
    }

    /** @return list<string> */
    public static function email(): array
    {
        return ['required', 'string', 'email', 'max:255'];
    }

    /** @return list<mixed>  Typed twice (password_confirmation) */
    public static function newPassword(): array
    {
        return ['required', 'confirmed', Password::defaults()];
    }
}
