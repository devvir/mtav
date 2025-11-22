<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Database\Factories\Concerns\InProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Log>
 */
class LogFactory extends Factory
{
    use InProject;

    public const EVENTS = [
        'User logged in',
        'User logged out',
        'Project updated',
        'Unit created',
        'Unit updated',
        'Unit deleted',
        'Family created',
        'Family updated',
        'Family deleted',
        'Member added to family',
        'Member removed from family',
        'Payment received',
        'Payment updated',
        'Document uploaded',
        'Document deleted',
        'Settings changed',
        'Report generated',
        'Email sent',
        'Notification created',
        'Task completed',
    ];

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id'    => $this->inSameProject(User::class),
            'event'      => fake()->randomElement(self::EVENTS),
        ];
    }
}
