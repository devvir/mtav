<?php

namespace Database\Factories;

use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

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
            'legal_id' => fake()->optional()->numerify('#.###.###-#'),
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'is_admin' => false,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'email_verified_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn () => [ 'email_verified_at' => null ]);
    }

    public function admin(): static
    {
        return $this->state([
            'is_admin'  => true,
            'family_id' => null,
        ]);
    }

    public function inFamily(Family $family): static
    {
        return $this->state([ 'family_id' => $family->id ]);
    }
}
