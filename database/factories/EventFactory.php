<?php

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'   => Project::factory(),
            'type'         => EventType::ONSITE,
            'title'        => fake()->sentence(3),
            'description'  => fake()->paragraph(),
            'location'     => fake()->address(),
            'start_date'   => fake()->dateTimeBetween('+1 day', '+2 months'),
            'end_date'     => null,
            'is_published' => true,
            'allows_rsvp'  => false,
        ];
    }
}
