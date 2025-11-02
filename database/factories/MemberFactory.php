<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'password' => bcrypt('password'),
            'remember_token' => \Illuminate\Support\Str::random(10),
            'invitation_accepted_at' => now(),
            'email_verified_at' => now(),
            'is_admin' => false,
        ];
    }

    /**
     * Indicate that the member should belong to a specific family.
     */
    public function inFamily(Family $family): static
    {
        return $this->state(['family_id' => $family->id]);
    }
}
