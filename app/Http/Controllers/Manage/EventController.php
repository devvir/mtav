<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Events of a project, drafts included. The lottery event is edited on the lottery page.
 */
class EventController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $events = $project->events()
            ->when($request->q, fn ($q, $search) => $q->search($search))
            ->with('rsvps')
            ->sorted()
            ->paginate(30)
            ->withQueryString();

        return inertia('Manage/Events/Index', [
            'events'  => EventResource::collection($events),
            'filters' => ['q' => $request->q],
        ]);
    }

    public function create(Project $project): Response
    {

        return inertia('Manage/Events/Create');
    }

    public function store(EventRequest $request, Project $project): RedirectResponse
    {

        $event = $project->events()->create([...$request->validated(), 'creator_id' => $request->user()->id]);

        return to_route('manage.events.index')->with('success', __('flash.event_created'));
    }

    public function edit(Project $project, Event $event): Response
    {
        abort_if($event->isLottery(), 404);

        return inertia('Manage/Events/Edit', ['event' => EventResource::make($event)]);
    }

    public function update(EventRequest $request, Project $project, Event $event): RedirectResponse
    {
        abort_if($event->isLottery(), 404);

        $event->update($request->validated());

        return to_route('manage.events.index')->with('success', __('flash.event_updated'));
    }

    public function destroy(Project $project, Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return to_route('manage.events.index')->with('success', __('flash.event_deleted'));
    }
}
