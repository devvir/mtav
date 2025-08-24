<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\User;
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
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
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
        return $this->state(fn () => [ 'is_admin' => true ]);
    }

    public function withFamily(?Family $family = null): static
    {
        $family ??= Family::factory()->create();

        return $this->afterCreating(
            fn (User $user) => $user->family()->associate($family)->save()
        );
    }

    public function withExistingFamily(?Family $family = null): static
    {
        $family ??= Family::inRandomOrder()->first();

        return $this->withFamily($family);
    }
}
