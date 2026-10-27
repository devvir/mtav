<?php

namespace Database\Factories;

use App\Enums\MediaCategory;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Media rows without files behind them (tests that need files upload them).
 *
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'category'    => MediaCategory::IMAGE,
            'path'        => 'projects/0/' . fake()->uuid() . '.jpg',
            'description' => fake()->sentence(),
            'mime_type'   => 'image/jpeg',
            'file_size'   => fake()->numberBetween(50_000, 2_000_000),
            'width'       => 1600,
            'height'      => 1200,
        ];
    }
}
