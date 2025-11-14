<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Project;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // NOTE: Lottery events are created automatically by ProjectObserver
        Project::with(['admins', 'members'])->get()->each(function (Project $project) {
            $adminIds = $project->admins->pluck('id');
            $memberIds = $project->members->pluck('id');

            // Create online events
            $onlineEvents = Event::factory()->count(rand(1, 3))->online()
                ->sequence(fn () => ['creator_id' => $adminIds->random()])
                ->create(['project_id' => $project->id]);

            // Create onsite events
            $onsiteEvents = Event::factory()->count(rand(1, 3))->onSite()
                ->sequence(fn () => ['creator_id' => $adminIds->random()])
                ->create(['project_id' => $project->id]);

            // Assign members to events that allow RSVP
            $allEvents = $onlineEvents->concat($onsiteEvents);

            $allEvents->filter(fn ($event) => $event->rsvp)->each(function ($event) use ($memberIds) {
                if ($memberIds->isNotEmpty()) {
                    $numRsvps = rand(0, min(10, $memberIds->count()));
                    $selectedMembers = $memberIds->random($numRsvps);

                    foreach ($selectedMembers as $memberId) {
                        $event->rsvps()->attach($memberId, [
                            'status' => rand(0, 1) ? true : (rand(0, 1) ? false : null),
                        ]);
                    }
                }
            });
        });
    }
}
