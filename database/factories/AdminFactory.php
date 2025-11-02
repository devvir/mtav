<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

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
            'password' => bcrypt('password'),
            'remember_token' => \Illuminate\Support\Str::random(10),
            'invitation_accepted_at' => now(),
            'email_verified_at' => now(),
            'is_admin' => true,
            'family_id' => null,
        ];
    }
}
