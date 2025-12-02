<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Services\Form\FormService;
use App\Services\Form\FormType;
use App\Services\LotteryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        $events = currentProject()->events()->sorted()
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
            'event' => $event->load('creator', 'project', 'rsvps'),
        ]);
    }

    public function create(): Response
    {
        $formSpecs = FormService::make(Event::class, FormType::CREATE);

        return inertia('Events/Create', [
            'form' => $formSpecs,
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
        $formSpecs = FormService::make($event, FormType::UPDATE);

        // Lottery events do not allow certain fields to be modified
        if ($event->isLottery()) {
            $formSpecs->removeSpecs('type', 'title', 'end_date', 'is_published');
        }

        return inertia('Events/Edit', [
            'form' => $formSpecs,
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $input = $request->validated();

        $event->isLottery()
            ? app(LotteryService::class)->updateLotteryEvent($event, $input)
            : $event->update($input);

        return to_route('events.show', $event)
            ->with('success', __('flash.event_updated'));
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return back()->with('success', __('flash.event_deleted'));
    }

    public function restore(Event $event): RedirectResponse
    {
        $event->restore();

        return to_route('events.show', $event)
            ->with('success', __('flash.event_restored'));
    }
}
