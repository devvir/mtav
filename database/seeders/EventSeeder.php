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
        Project::with('admins')->get()->each(function (Project $project) {
            $adminIds = $project->admins->pluck('id');

            Event::factory()->count(rand(1, 3))->online()
                ->sequence(fn () => ['creator_id' => $adminIds->random()])
                ->create(['project_id' => $project->id]);

            Event::factory()->count(rand(1, 3))->onSite()
                ->sequence(fn () => ['creator_id' => $adminIds->random()])
                ->create(['project_id' => $project->id]);
        });
    }
}
