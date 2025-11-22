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

    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'owner_id'    => $this->inSameProject(User::class),
            'description' => fake()->sentence(),
            'alt_text'    => fake()->optional(0.4)->words(3, true),
            'file_size'   => fake()->numberBetween(50000, 5000000), // 50KB to 5MB
        ];
    }

    public function configure()
    {
        return $this->category(
            fake()->randomElement(MediaCategory::cases())
        );
    }

    public function ownedBy(User|int $userOrId): static
    {
        return $this->state([
            'owner_id' => $userOrId instanceof User ? $userOrId->id : $userOrId,
        ]);
    }

    public function category(MediaCategory $category): static
    {
        return $this->state(fn () => [
            'category'  => $category,
            'mime_type' => $this->getMimeType($category),
            'path'      => $this->generatePath($category),
            'thumbnail' => fn (array $attributes) => $attributes['path'],
            'width'     => $category === MediaCategory::IMAGE ? fake()->numberBetween(800, 2400) : null,
            'height'    => $category === MediaCategory::IMAGE ? fake()->numberBetween(600, 1800) : null,
        ]);
    }

    private function generatePath(MediaCategory $category): string
    {
        $extensions = [
            'audio'    => ['mp3', 'wav'],
            'image'    => ['jpg', 'png', 'gif', 'webp'],
            'document' => ['pdf', 'docx', 'xlsx', 'txt'],
            'video'    => ['mp4', 'avi', 'mov'],
        ];

        $uuid = fake()->uuid();
        $extension = fake()->randomElement($extensions[$category->value] ?? ['dat']);

        return "media/dev-{$uuid}.{$extension}";
    }

    private function getMimeType(MediaCategory $category): string
    {
        $mimeTypes = [
            'audio'    => ['audio/mpeg', 'audio/wav'],
            'image'    => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'document' => ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'],
            'video'    => ['video/mp4', 'video/avi', 'video/quicktime'],
        ];

        return fake()->randomElement($mimeTypes[$category->value] ?? ['application/octet-stream']);
    }
}
