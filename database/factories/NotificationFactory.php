<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $notifiableType = fake()->randomElement([null, User::class, Project::class]);

        return [
            'type'            => fake()->randomElement(NotificationType::cases())->value,
            'notifiable_type' => $notifiableType,
            'notifiable_id'   => $this->notifiableId($notifiableType),
            'triggered_by'    => fake()->boolean() ? User::inRandomOrder()->first()?->id : null,
            'data'            => [
                'message'  => fake()->sentence(),
                'type'     => fake()->randomElement(['created', 'updated', 'deleted', 'restored']),
                'model'    => fake()->randomElement(['Project', 'Family', 'Member', 'Unit']),
                'model_id' => fake()->numberBetween(1, 100),
            ],
        ];
    }

    /**
     * Get the notifiable_id based on the notifiable_type.
     */
    private function notifiableId(?string $notifiableType): ?int
    {
        return match ($notifiableType) {
            null           => null,
            User::class    => User::inRandomOrder()->first()?->id ?? User::factory()->create()->id,
            Project::class => Project::inRandomOrder()->first()?->id ?? Project::factory()->create()->id,
        };
    }

    /**
     * Create a global notification.
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'notifiable_type' => null,
            'notifiable_id'   => null,
        ]);
    }

    /**
     * Create a user notification.
     */
    public function forUser(?User $user = null): static
    {
        $user ??= User::factory()->create();

        return $this->state(fn (array $attributes) => [
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
        ]);
    }

    /**
     * Create a project notification.
     */
    public function forProject(?Project $project = null): static
    {
        $project ??= Project::factory()->create();

        return $this->state(fn (array $attributes) => [
            'notifiable_type' => Project::class,
            'notifiable_id'   => $project->id,
        ]);
    }
}
