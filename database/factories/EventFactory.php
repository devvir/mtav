<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Admin;
use App\Models\Project;
use Database\Factories\Concerns\InProject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    use InProject;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id'   => Project::factory(),
            'creator_id'   => $this->inSameProject(Admin::class),
            'start_date'   => fake()->optional(0.8)->dateTimeBetween('-1 month', '+3 month'),
            'title'        => fake()->sentence(3),
            'description'  => fake()->paragraph(3),
            'type'         => fake()->randomElement([EventType::ONLINE, EventType::ONSITE]),
            'is_published' => fake()->boolean(80),
            'rsvp'         => fake()->boolean(30), // Increased chance for RSVP events
        ];
    }

    public function configure()
    {
        return $this->state(function (array $attributes) {
            $start = $attributes['start_date'] ? Carbon::parse($attributes['start_date']) : null;

            return [
                'end_date' => $start ? fake()->optional(0.5)->dateTimeBetween($start->addMinutes(5), $start->addHours(2)) : null,
                'location' => match ($attributes['type']) {
                    EventType::ONLINE => fake()->optional(0.6)->url(),
                    EventType::ONSITE => fake()->optional(0.6)->address(),
                    default           => null,
                },
            ];
        });
    }

    /**
     * Create a lottery event.
     */
    public function lottery(): static
    {
        return $this->state(fn () => [
            'title'        => __('Lottery'),
            'description'  => __("general.lottery_default_description"),
            'type'         => EventType::LOTTERY,
            'is_published' => true,
        ]);
    }

    /**
     * Create an online event.
     */
    public function online(): static
    {
        return $this->state(fn () => [
            'type'     => EventType::ONLINE,
            'location' => fake()->url(),
        ]);
    }

    /**
     * Create an on-site event.
     */
    public function onSite(): static
    {
        return $this->state(fn () => [
            'type'     => EventType::ONSITE,
            'location' => fake()->address(),
        ]);
    }
}
