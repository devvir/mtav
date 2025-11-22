<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Project;
use Exception;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * NOTE: Lottery events are created automatically by ProjectObserver
     */
    public function run(): void
    {
        Project::with('admins', 'members')->get()->each(
            fn (Project $project) => Event::factory()
                ->count(rand(5, 20))
                ->inProject($project)
                ->recycle($project->admins)
                ->create()
                ->each(function (Event $event) use ($project) {
                    $membersCount = $project->members->count();

                    if ($event->rsvp && $membersCount) {
                        $rsvpsTrue = $project->members->random(rand(1, floor($membersCount / 2)));
                        $rsvpsFalse = $project->members->random(rand(1, floor($membersCount / 2)));

                        try {
                            $event->rsvps()->attach($rsvpsTrue, [ 'status' => true ]);
                            $event->rsvps()->attach($rsvpsFalse, [ 'status' => false ]);
                        } catch (Exception $e) {
                            // Ignore duplicate entries
                        }
                    }
                })
        );
    }
}
