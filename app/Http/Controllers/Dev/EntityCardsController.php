<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Event;
use App\Models\Family;
use App\Models\Log;
use App\Models\Member;
use App\Models\Project;
use App\Models\Unit;
use Inertia\Response;

class EntityCardsController extends Controller
{
    /**
     * Display entity card previews with real data.
     */
    public function __invoke(): Response
    {
        // Fetch 3 random projects but replace first one with current project if set
        $projects = Project::inRandomOrder()->take(3)->get();
        Project::current() && $projects->replace([0 => Project::current()]);
        $projects->loadCount('admins', 'members', 'families', 'units');

        $admins = Admin::inRandomOrder()->take(3)->withCount('projects')->get();
        $members = Member::inRandomOrder()->take(3)->with('family', 'projects')->get();
        $families = Family::inRandomOrder()->take(3)->with('project', 'unitType', 'members')->get();
        $units = Unit::inRandomOrder()->take(3)->with('project', 'type', 'family')->get();
        $gallery = collect($this->mediaMock());
        $events = Event::inRandomOrder()->take(3)->with('project', 'rsvps')->get();
        $logs = Log::inRandomOrder()->take(3)->with('user', 'project')->get();

        return inertia('Dev/EntityCards', [
            'projects' => $projects,
            'units'    => $units,
            'admins'   => $admins,
            'members'  => $members,
            'families' => $families,
            'events'   => $events,
            'logs'     => $logs,
            'gallery'  => $gallery,
        ]);
    }

    protected function mediaMock(): array
    {
        return [
            (object) [
                'id'          => 1,
                'title'       => 'Community Garden',
                'description' => 'Photos from our shared garden space.',
                'created_at'  => now()->subWeeks(2),
                'created_ago' => '2 weeks ago',
                'deleted_at'  => null,
                '_actions'    => 'A',
                'project'     => (object) ['id' => 1, 'name' => 'Verde'],
                'media_count' => 12,
                'allows'      => [
                    'view'    => true,
                    'update'  => true,
                    'delete'  => true,
                    'restore' => false
                ]
            ],
            (object) [
                'id'          => 2,
                'title'       => 'Construction Progress - Building A Complex with Multiple Phases and Timeline Documentation',
                'description' => 'Comprehensive documentation of the construction progress for Building A, including foundation work, structural development, interior finishing, and landscaping. This gallery serves as a historical record of the project development and milestone achievements throughout the construction timeline.',
                'created_at'  => now()->subMonths(1),
                'created_ago' => '1 month ago',
                'deleted_at'  => null,
                '_actions'    => 'B',
                'project'     => (object) ['id' => 2, 'name' => 'Las Flores'],
                'media_count' => 45,
                'allows'      => [
                    'view'    => true,
                    'update'  => true,
                    'delete'  => false,
                    'restore' => false
                ]
            ],
            (object) [
                'id'          => 3,
                'title'       => 'Annual Celebration',
                'description' => 'Photos from our yearly community celebration.',
                'created_at'  => now()->subDays(5),
                'created_ago' => '5 days ago',
                'deleted_at'  => null,
                '_actions'    => 'C',
                'project'     => (object) ['id' => 3, 'name' => 'Sunset Hills'],
                'media_count' => 8,
                'allows'      => [
                    'view'    => true,
                    'update'  => false,
                    'delete'  => false,
                    'restore' => false
                ]
            ]
        ];
    }
}
