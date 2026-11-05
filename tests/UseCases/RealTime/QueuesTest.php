<?php

use App\Events\MessageSent;
use App\Events\NotificationSent;
use App\Events\OpenLotteryChanged;
use App\Events\ProjectChanged;
use App\Jobs\AnswerInChat;
use App\Jobs\PushMessage;
use App\Jobs\PushNotification;
use App\Models\Message;
use App\Models\Notification;

/**
 * What reaches people at once waits behind nothing long: live updates and devices have their
 * queue, the Assistant its own, and every server runs a worker for each.
 */

it('sends live updates apart from what takes long', function ($event) {
    expect($event->broadcastQueue())->toBe('realtime');
})->with([
    'a message'           => fn () => new MessageSent(new Message(), []),
    'a notification'      => fn () => new NotificationSent(new Notification()),
    'a project\'s change' => fn () => new ProjectChanged(1, 'families', 'created', 1),
    'an open lottery'     => fn () => new OpenLotteryChanged('slug'),
]);

it('sends to devices apart from what takes long, and answers apart from them both', function () {
    expect((new PushNotification(1))->queue)->toBe('realtime')
        ->and((new PushMessage(1))->queue)->toBe('realtime')
        ->and((new AnswerInChat(1))->queue)->toBe('assistant');
});

describe('On a server', function () {
    // Each service with the command that works a queue: its name, and the queue it names
    $workers = function () {
        preg_match_all('/^  ([\w-]+):\n(?:(?!^  \S).*\n)*?\s+command: (.*queue:work.*)$/m', file_get_contents(base_path('deploy/slot.yml')), $found, PREG_SET_ORDER);

        return collect($found)->mapWithKeys(fn ($service) => [
            $service[1] => preg_match("/--queue=([\w,]+)/", $service[2], $queue) ? $queue[1] : 'default',
        ]);
    };

    it('runs one worker per queue', function () use ($workers) {
        expect($workers()->sort()->values()->all())->toBe(['assistant', 'default', 'realtime']);
    });

    it('stops every worker of the slot it leaves, going forward and going back', function () {
        // Taken down whole, the slot's every service stops: its workers among them, none left out
        $downs = collect(file(base_path('deploy/host/deploy')))
            ->filter(fn ($line) => str_contains($line, 'slot_compose "$live_slot" "$live_image" down'));

        expect($downs)->toHaveCount(2)
            ->and($downs->every(fn ($line) => trim(\Illuminate\Support\Str::after($line, 'down')) === ''))->toBeTrue();
    });
});
