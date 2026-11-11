<?php

namespace App\Http\Requests;

/**
 * An admin registering a family, with the first person invited to it: alone, nobody could
 * ever answer for it.
 */
class NewFamilyRequest extends FamilyRequest
{
    public function rules(): array
    {
        return [...parent::rules(), ...PersonRules::invitee()];
    }

    /** @return array{name: string, unit_type_id: int|string} */
    public function family(): array
    {
        return $this->safe()->only('name', 'unit_type_id');
    }

    /** @return array{email: string, firstname?: ?string, lastname?: ?string} */
    public function person(): array
    {
        return $this->safe()->only('email', 'firstname', 'lastname');
    }
}
