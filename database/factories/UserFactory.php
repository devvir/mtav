<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email'                  => fake()->unique()->safeEmail(),
            'phone'                  => fake()->phoneNumber(),
            'legal_id'               => fake()->optional()->numerify('#.###.###-#'),
            'firstname'              => fake()->firstName(),
            'lastname'               => fake()->lastName(),
            'about'                  => fake()->optional(0.7)->realText(200),
            'is_admin'               => fake()->boolean(0.1),
            'password'               => once(fn () => Hash::make('password')),
            'remember_token'         => Str::random(10),
            'invitation_accepted_at' => now(),
            'email_verified_at'      => now(),
        ];
    }

    /**
     * Indicate that the user hasn't accepted invitation yet.
     */
    public function unverified(): static
    {
        return $this->state(fn () => [
            'invitation_accepted_at' => null,
            'email_verified_at'      => null,
        ]);
    }

    public function member(): static
    {
        return $this->state([
            'is_admin'  => false,
            'family_id' => Family::factory(),
        ]);
    }

    public function admin(): static
    {
        return $this->state([
            'is_admin'  => true,
            'family_id' => null,
        ]);
    }

    public function inProject(Project|int $projectOrId): static
    {
        $project = model($projectOrId, Project::class);

        return $this->afterCreating(
            fn (User $user) => match (true) {
                $user instanceof Member => $project->addMember($user),
                $user instanceof Admin  => $project->addAdmin($user),
                default                 => $user->is_admin ? $project->addAdmin($user->id) : $project->addMember($user->id),
            }
        );
    }
}
