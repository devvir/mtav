<?php

use App\Models\ErrorEntry;
use App\Notifications\NewErrorNotice;
use App\Services\ErrorLog;
use Illuminate\Support\Facades\Notification;

/**
 * The app keeps its own errors for its superadmins: each once, counted, the owner told
 * the first time; marked solved, it goes.
 */

beforeEach(function () {
    config(['errors.log' => true]);
    Notification::fake();
    $this->owner = aUser();
    $this->owner->forceFill(['is_owner' => true, 'is_superadmin' => true])->save();
});

/** The same error, from the same place, every time */
function sameError(): RuntimeException
{
    return new RuntimeException('The plan could not be read');
}

it('keeps an error once, counting each time it happens again, and tells the owner once', function () {
    foreach (range(1, 3) as $_) {
        ErrorLog::capture(sameError());
    }

    expect(ErrorEntry::sole())->count->toBe(3)->message->toBe('The plan could not be read');
    Notification::assertSentToTimes($this->owner, NewErrorNotice::class, 1);
});

it('reports what the app reports', function () {
    report(new LogicException('Something unexpected'));

    expect(ErrorEntry::sole()->class)->toBe(LogicException::class);
});

it('shows superadmins the errors, and lets them mark one solved', function () {
    ErrorLog::capture(sameError());

    $errors = $this->as($this->owner)->get(route('admin.errors.index'))->inertiaProps('errors.data');
    expect($errors)->toHaveCount(1);

    $this->as($this->owner)->delete(route('admin.errors.destroy', $errors[0]['id']));
    expect(ErrorEntry::count())->toBe(0);
});

it('shows the errors to no one else', function () {
    expect($this->as(aUser())->get(route('admin.errors.index')))->toBeDenied();
});
