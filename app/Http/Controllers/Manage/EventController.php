<?php

namespace App\Http\Controllers\Manage;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;

/**
 * Creating, changing and cancelling events: admin tools on the events pages everyone uses.
 * The lottery event is edited on the lottery page.
 */
class EventController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function store(EventRequest $request, Project $project): RedirectResponse
    {
        $event = $project->events()->create([...$request->validated(), 'creator_id' => $request->user()->id]);

        $event->is_published && $this->announce($event, $request->user());

        return to_route('events.index', ['event' => $event->id])->with('success', __('flash.event_created'));
    }

    public function update(EventRequest $request, Project $project, Event $event): RedirectResponse
    {
        abort_if($event->isLottery(), 404);

        $wasPublished = $event->is_published;
        $event->update($request->validated());

        ! $wasPublished && $event->is_published && $this->announce($event, $request->user());

        return to_route('events.index', ['event' => $event->id])->with('success', __('flash.event_updated'));
    }

    public function destroy(Project $project, Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->retire();

        return to_route('events.index')->with('success', __('flash.event_deleted'));
    }

    /** Its people hear of it once it is out */
    private function announce(Event $event, User $by): void
    {
        $this->notifications->send(
            NotificationTarget::PROJECT,
            NotificationType::EVENT_PUBLISHED,
            title: 'notifications.event_published_title',
            message: 'notifications.event_published_message',
            params: ['title' => $event->title],
            url: route('events.index', ['project' => $event->project_id, 'event' => $event->id]),
            trigger: $by,
            targetId: $event->project_id,
        );
    }
}
