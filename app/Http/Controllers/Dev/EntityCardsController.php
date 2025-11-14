<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Event;
use App\Models\Family;
use App\Models\Log;
use App\Models\Media;
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
        $media = Media::inRandomOrder()->take(3)->with('owner', 'project')->get();
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
            'media'    => $media,
        ]);
    }
}
