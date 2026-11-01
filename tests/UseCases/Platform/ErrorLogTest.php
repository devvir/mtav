<?php

use App\Models\ErrorEntry;
use App\Notifications\NewErrorNotice;
use App\Services\ErrorLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

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

describe('Telling errors apart', function () {
    /** An error thrown from the framework's own code, by this line of ours */
    function aFrameworkError(): Throwable
    {
        try {
            collect()->firstOrFail();
        } catch (Throwable $error) {
            return $error;
        }
    }

    /** The same one, by another line of ours */
    function theSameFromElsewhere(): Throwable
    {
        try {
            collect()->firstOrFail();
        } catch (Throwable $error) {
            return $error;
        }
    }

    function aFailedQuery(string $sql, array $bindings = []): QueryException
    {
        try {
            DB::select($sql, $bindings);
        } catch (QueryException $error) {
            return $error;
        }
    }

    it('keeps apart what the framework throws from the same line, led there from different places', function () {
        ErrorLog::capture(aFrameworkError());
        ErrorLog::capture(theSameFromElsewhere());

        expect(ErrorEntry::count())->toBe(2);
        Notification::assertSentToTimes($this->owner, NewErrorNotice::class, 2);
    });

    it('still counts as one what the framework throws, led there from the same place', function () {
        foreach (range(1, 2) as $_) {
            ErrorLog::capture(aFrameworkError());
        }

        expect(ErrorEntry::sole()->count)->toBe(2);
    });

    it('keeps apart different queries failing, and counts one failing with other values as one', function () {
        $failing = fn (string $sql, array $bindings = []) => ErrorLog::capture(aFailedQuery($sql, $bindings));

        foreach ([1, 2] as $value) {
            $failing('select * from no_such_table where id = ?', [$value]);
        }
        $failing('select * from nor_this_one where id = ?', [1]);

        expect(ErrorEntry::count())->toBe(2)
            ->and(ErrorEntry::orderBy('id')->first()->count)->toBe(2);
    });
});

describe('Keeping secrets out', function () {
    it('keeps a query\'s values out of what it stores and shows', function () {
        try {
            DB::select('select * from no_such_table where email = ?', ['ana@example.com']);
        } catch (QueryException $error) {
            ErrorLog::capture($error);
        }

        $entry = ErrorEntry::sole();
        expect($entry->message)->toContain('no_such_table')->toContain('SQLSTATE')->not->toContain('ana@example.com')
            ->and($entry->trace)->not->toContain('ana@example.com');
    });

    it('keeps a value passed along the way out of the trace', function () {
        $fails = function (string $password) {
            throw new RuntimeException('The plan could not be read');
        };

        try {
            $fails('hunter2hunter2');
        } catch (RuntimeException $error) {
            ErrorLog::capture($error);
        }

        expect(ErrorEntry::sole()->trace)->toContain('#0')->not->toContain('hunter2');
    });

    it('keeps a reset token, an email and a signature out of the address', function () {
        Route::middleware('web')->get('boom/{token}', fn () => ErrorLog::capture(sameError()));

        $this->get('boom/s3cr3t-token?email=ana@example.com&signature=abc');

        expect(ErrorEntry::sole()->url)->toEndWith('/boom/…');
    });

    it('keeps an open lottery\'s key out of the address', function () {
        Route::middleware('web')->get('boom/{key}/more', fn () => ErrorLog::capture(sameError()));

        $this->get('boom/s3cr3t-key/more');

        expect(ErrorEntry::sole()->url)->toEndWith('/boom/…/more');
    });

    it('keeps the address of a page with nothing secret', function () {
        Route::middleware('web')->get('boom/{thing}', fn () => ErrorLog::capture(sameError()));

        $this->get('boom/shown');

        expect(ErrorEntry::sole()->url)->toEndWith('/boom/shown');
    });

    it('mails which error and where, never its message', function () {
        ErrorLog::capture(new RuntimeException('ana@example.com could not be read'));

        Notification::assertSentTo($this->owner, NewErrorNotice::class, function (NewErrorNotice $notice) {
            $lines = implode(' ', $notice->toMail($this->owner)->introLines);

            return str_contains($lines, RuntimeException::class) && ! str_contains($lines, 'ana@example.com');
        });
    });
});

it('has no address for an error in a command', function () {
    ErrorLog::capture(sameError());

    expect(ErrorEntry::sole()->url)->toBeNull();
});
