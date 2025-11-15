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

            // Create 2-10 events per project with random types
            $totalEvents = rand(2, 10);

            for ($i = 0; $i < $totalEvents; $i++) {
                // Randomly choose online or onsite for each event
                $isOnline = rand(0, 1);

                if ($isOnline) {
                    $event = Event::factory()->online()
                        ->create([
                            'project_id' => $project->id,
                            'creator_id' => $adminIds->random()
                        ]);
                } else {
                    $event = Event::factory()->onSite()
                        ->create([
                            'project_id' => $project->id,
                            'creator_id' => $adminIds->random()
                        ]);
                }

                // Assign members to events that allow RSVP
                if ($event->rsvp && $memberIds->isNotEmpty()) {
                    $numRsvps = rand(0, min(10, $memberIds->count()));
                    $selectedMembers = $memberIds->random($numRsvps);

                    foreach ($selectedMembers as $memberId) {
                        $event->rsvps()->attach($memberId, [
                            'status' => rand(0, 1) ? true : (rand(0, 1) ? false : null),
                        ]);
                    }
                }
            }
        });
    }
}
