<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

/**
 * Creating, changing and cancelling events: admin tools on the events pages everyone uses.
 * The lottery event is edited on the lottery page.
 */
class EventController extends Controller
{
    public function store(EventRequest $request, Project $project): RedirectResponse
    {
        $event = $project->events()->create([...$request->validated(), 'creator_id' => $request->user()->id]);

        return to_route('events.show', $event)->with('success', __('flash.event_created'));
    }

    public function update(EventRequest $request, Project $project, Event $event): RedirectResponse
    {
        abort_if($event->isLottery(), 404);

        $event->update($request->validated());

        return back()->with('success', __('flash.event_updated'));
    }

    public function destroy(Project $project, Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return to_route('events.index')->with('success', __('flash.event_deleted'));
    }
}
