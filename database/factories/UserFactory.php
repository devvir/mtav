<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email'             => fake()->unique()->safeEmail(),
            'firstname'         => fake()->firstName(),
            'lastname'          => fake()->lastName(),
            'phone'             => fake()->optional()->phoneNumber(),
            'password'          => config('app.demo_password') ?? 'password',
            'email_verified_at' => now(),
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function superadmin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill(['is_superadmin' => true])->save());
    }
}
