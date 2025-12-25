<?php

namespace Database\Factories;

use App\Enums\MediaCategory;
use App\Models\Project;
use App\Models\User;
use Database\Factories\Concerns\InProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    use InProject;

    /**
     * Define the model's default state.
     *
     * Note: This factory is primarily used for testing with real files.
     * For seeding, use MediaSeeder which handles actual sample files.
     */
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'owner_id'    => $this->inSameProject(User::class),
            'description' => fake()->sentence(),
            'alt_text'    => fake()->optional(0.4)->words(3, true),
            'file_size'   => fake()->numberBetween(50000, 5000000), // 50KB to 5MB
            'category'    => MediaCategory::IMAGE,
            'mime_type'   => 'image/jpeg',
            'path'        => 'media/test-' . fake()->uuid() . '.jpg',
            'thumbnail'   => fn (array $attributes) => $attributes['path'],
            'width'       => fake()->numberBetween(800, 2400),
            'height'      => fake()->numberBetween(600, 1800),
        ];
    }

    public function ownedBy(User|int $userOrId): static
    {
        return $this->state([
            'owner_id' => $userOrId instanceof User ? $userOrId->id : $userOrId,
        ]);
    }

    public function image(): static
    {
        return $this->state([
            'category'  => MediaCategory::IMAGE,
            'mime_type' => fake()->randomElement(['image/jpeg', 'image/png', 'image/gif', 'image/webp']),
            'path'      => 'media/test-' . fake()->uuid() . '.jpg',
            'width'     => fake()->numberBetween(800, 2400),
            'height'    => fake()->numberBetween(600, 1800),
        ]);
    }

    public function video(): static
    {
        return $this->state([
            'category'  => MediaCategory::VIDEO,
            'mime_type' => fake()->randomElement(['video/mp4', 'video/webm']),
            'path'      => 'media/test-' . fake()->uuid() . '.mp4',
            'width'     => 1920,
            'height'    => 1080,
        ]);
    }

    public function audio(): static
    {
        return $this->state([
            'category'  => MediaCategory::AUDIO,
            'mime_type' => fake()->randomElement(['audio/mpeg', 'audio/wav']),
            'path'      => 'media/test-' . fake()->uuid() . '.mp3',
            'width'     => null,
            'height'    => null,
        ]);
    }

    public function document(): static
    {
        return $this->state([
            'category'  => MediaCategory::DOCUMENT,
            'mime_type' => fake()->randomElement(['application/pdf', 'text/plain']),
            'path'      => 'media/test-' . fake()->uuid() . '.pdf',
            'width'     => null,
            'height'    => null,
        ]);
    }
}
