<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * The project's events (admins also see drafts, and create, edit and cancel them from
 * here), and answering whether one attends.
 */
class EventController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $past = $request->query('when') === 'past';
        $manages = $request->user()->can('update', $project);

        $events = $project->events()
            ->when(! $manages, fn ($q) => $q->published())
            ->when($request->q, fn ($q, $search) => $q->search($search))
            ->when($past, fn ($q) => $q->where('start_date', '<', now()), fn ($q) => $q->upcoming())
            ->with('rsvps')
            ->sorted($past ? 'desc' : 'asc')
            ->paginate(30)
            ->withQueryString();

        return inertia('Member/Events/Index', [
            'events'  => EventResource::collection($events),
            'filters' => ['q' => $request->q, 'when' => $past ? 'past' : 'upcoming'],
            // An event's details, shown over the list (?event=5); drafts for admins only
            'event' => fn () => ($event = $project->events()->find($request->integer('event')))
                && $request->user()->can('view', $event)
                ? EventResource::make($event->load('creator', 'rsvps'))
                : null,
        ]);
    }

    /** An event's details open over the list: its address leads there */
    public function show(Project $project, Event $event): RedirectResponse
    {
        $this->authorize('view', $event);

        return to_route('events.index', ['event' => $event->id]);
    }

    public function rsvp(Request $request, Project $project, Event $event): RedirectResponse
    {
        $this->authorize('rsvp', $event);

        $attending = $request->validate(['attending' => ['required', 'boolean']])['attending'];

        $event->rsvps()->syncWithoutDetaching([$request->user()->id => ['attending' => $attending]]);

        return back()->with('success', __($attending ? 'flash.rsvp_attending' : 'flash.rsvp_not_attending'));
    }
}
