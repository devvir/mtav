<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    public function definition(): array
    {
        $categories = ['image', 'document', 'video', 'archive'];
        $category = fake()->randomElement($categories);

        return [
            'owner_id'    => User::factory(),
            'project_id'  => Project::factory(),
            'path'        => $this->generatePath($category),
            'description' => fake()->sentence(),
            'alt_text'    => fake()->optional(0.4)->words(3, true),
            'width'       => in_array($category, ['image', 'video']) ? fake()->numberBetween(800, 2400) : null,
            'height'      => in_array($category, ['image', 'video']) ? fake()->numberBetween(600, 1800) : null,
            'category'    => $category,
            'mime_type'   => $this->getMimeType($category),
            'file_size'   => fake()->numberBetween(50000, 5000000), // 50KB to 5MB
        ];
    }

    public function inProject(Project $project): static
    {
        return $this->state([
            'project_id' => $project->id,
        ]);
    }

    public function ownedBy(User $user): static
    {
        return $this->state([
            'owner_id' => $user->id,
        ]);
    }

    public function category(string $category): static
    {
        return $this->state(function () use ($category) {
            return [
                'category'  => $category,
                'mime_type' => $this->getMimeType($category),
                'path'      => $this->generatePath($category),
                'width'     => $category === 'image' ? fake()->numberBetween(800, 2400) : null,
                'height'    => $category === 'image' ? fake()->numberBetween(600, 1800) : null,
            ];
        });
    }

    private function generatePath(string $category): string
    {
        $extensions = [
            'image'    => ['jpg', 'png', 'gif', 'webp'],
            'document' => ['pdf', 'docx', 'xlsx', 'txt'],
            'video'    => ['mp4', 'avi', 'mov'],
            'archive'  => ['zip', 'rar'],
        ];

        $extension = fake()->randomElement($extensions[$category] ?? ['dat']);
        $uuid = fake()->uuid();

        // Factory always creates dev files with dev flag for mock images
        $filename = "dev-{$uuid}.{$extension}";

        return "media/{$filename}";
    }    private function getMimeType(string $category): string
    {
        $mimeTypes = [
            'image'    => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'document' => ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'],
            'video'    => ['video/mp4', 'video/avi', 'video/quicktime'],
            'archive'  => ['application/zip', 'application/x-rar-compressed'],
        ];

        return fake()->randomElement($mimeTypes[$category] ?? ['application/octet-stream']);
    }
}
