<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker
            ->optional(0.7)
            ->dateTimeBetween('+1 week', '+6 months');

        $endDate = $startDate
            ? $this->faker
                ->optional(0.7)
                ->dateTimeBetween($startDate, $startDate->format('Y-m-d H:i:s').' +4 hours')
            : null;

        return [
            'project_id' => Project::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(3),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $this->faker->randomElement([EventType::ONLINE, EventType::ONSITE]),
            'location' => $this->faker->optional(0.6)->address(),
            'is_published' => $this->faker->boolean(80),
        ];
    }

    /**
     * Create a lottery event.
     */
    public function lottery(): static
    {
        return $this->state(fn () => [
            'title' => __('Lottery'),
            'description' => __('The lottery event determines the allocation of units based on family preferences. This is a fair and transparent process where each family\'s preferences are considered according to the established lottery algorithm.'),
            'type' => EventType::LOTTERY,
            'is_published' => true,
        ]);
    }

    /**
     * Create an online event.
     */
    public function online(): static
    {
        return $this->state(fn () => [
            'type' => EventType::ONLINE,
            'location' => $this->faker->url(),
        ]);
    }

    /**
     * Create an on-site event.
     */
    public function onSite(): static
    {
        return $this->state(fn () => [
            'type' => EventType::ONSITE,
            'location' => $this->faker->address(),
        ]);
    }
}
