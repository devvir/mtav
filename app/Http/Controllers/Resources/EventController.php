<?php

namespace App\Http\Controllers\Resources;

use App\Enums\EventType;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        $events = currentProject()->events()->upcoming()->sorted()
            ->with('project')
            ->when($request->user()->isMember(), fn ($query) => $query->published())
            ->when($request->q, fn ($q, $search) => $q->search($search));

        return inertia('Events/Index', [
            'events' => Inertia::deepMerge(fn () => $events->paginate(30)),
        ]);
    }

    public function show(Event $event): Response
    {
        return inertia('Events/Show', [
            'event' => $event->load('creator', 'project'),
        ]);
    }

    public function create(): Response
    {
        return inertia('Events/Create', [
            'types' => Arr::except(EventType::labels(), 'lottery'),
        ]);
    }

    public function store(CreateEventRequest $request): RedirectResponse
    {
        $event = currentProject()->events()->save(
            Event::make($request->validated())
        );

        return to_route('events.show', $event)
            ->with('success', __('flash.event_created'));
    }

    public function edit(Event $event): Response
    {
        return inertia('Events/Edit', [
            'event' => $event,
            'types' => Arr::except(EventType::labels(), 'lottery'),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

        return to_route('events.show', $event)
            ->with('success', __('flash.event_updated'));
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return back()->with('success', __('flash.event_deleted'));
    }
}
