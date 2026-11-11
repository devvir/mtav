<?php

use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Pest\Browser\Playwright\Client;
use Pest\Browser\Playwright\Playwright;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Helpers for browser tests: what arrives by email, and dialogs.
 */

/**
 * The link of the latest invitation emailed to an address (needs Notification::fake()).
 */
function invitationLinkFor(string $email): string
{
    $link = null;

    Notification::assertSentOnDemand(InvitationNotification::class, function ($notification) use ($email, &$link) {
        if ($notification->invitation->email === $email) {
            $link = route('invitation.show', $notification->token);
        }

        return $notification->invitation->email === $email;
    });

    return $link;
}

/**
 * The link of the latest password reset emailed to a user (needs Notification::fake()).
 */
function passwordResetLinkFor(User $user): string
{
    $link = null;

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user, &$link) {
        $link = route('password.reset', ['token' => $notification->token, 'email' => $user->email]);

        return true;
    });

    return $link;
}

/**
 * The email confirmation link of a user (for their address, or a new one).
 */
function emailConfirmationLinkFor(User $user, ?string $email = null): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id'   => $user->id,
        'hash' => sha1($email ?? $user->email),
    ]);
}

/**
 * Selector of a button in the dialog on top, by its exact text: never a page button, nor
 * one in a dialog underneath (a confirmation over some details), nor one whose text only
 * starts the same ("Remove" is not "Remove from the project").
 */
function inDialog(string $text): string
{
    return "[role=\"dialog\"] >> nth=-1 >> button >> text=\"{$text}\"";
}

/**
 * Selector of a form field in the dialog on top, by its name: never the same field of a
 * dialog still closing underneath (adding a family, then editing it at once)
 */
function inDialogField(string $name): string
{
    return "[role=\"dialog\"] >> nth=-1 >> [name=\"{$name}\"]";
}

/**
 * Puts a file in a file input, as if the person had picked it.
 *
 * The browser runs as a server that refuses local paths, so the file travels inside
 * the page script instead. Attaching the same file name twice to one input attaches it once.
 */
function attachFile(object $page, string $selector, string $path): object
{
    $file = json_encode([
        'name'    => basename($path),
        'type'    => mime_content_type($path) ?: 'application/octet-stream',
        'content' => base64_encode(file_get_contents($path)),
    ]);
    $selector = json_encode($selector);

    $page->script(<<<JS
        () => {
            const file = {$file};
            const bytes = Uint8Array.from(atob(file.content), (char) => char.charCodeAt(0));
            const input = document.querySelector({$selector});
            // The plugin may run a script again after a slow first try: attach once
            if (input.dataset.attached === file.name) return;
            input.dataset.attached = file.name;
            const transfer = new DataTransfer();
            transfer.items.add(new File([bytes], file.name, { type: file.type }));
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        JS);

    return $page;
}

/**
 * Selector of a button by its text (never a heading or a paragraph saying the same).
 */
function button(string $text): string
{
    return "button:has-text(\"{$text}\")";
}

/** A real file on disk for the browser to upload */
function fixtureFile(string $name, string $content): string
{
    $path = sys_get_temp_dir() . '/' . $name;
    file_put_contents($path, $content);

    return $path;
}

/** A small PNG on disk */
function pngFile(string $name = 'photo.png'): string
{
    $image = imagecreatetruecolor(64, 48);
    imagefill($image, 0, 0, imagecolorallocate($image, 30, 120, 200));
    ob_start();
    imagepng($image);

    return fixtureFile($name, ob_get_clean());
}

/**
 * Waits until a check on the database holds, for what only the server can say (a save the page
 * sends a moment later), as long as assertions wait; then it fails with the check's own message.
 * The app runs in this process: the page's wait lets it answer meanwhile, a plain sleep would not.
 */
function eventually(object $page, Closure $check): void
{
    $until = microtime(true) + Playwright::timeout() / 1000;

    while (true) {
        try {
            $check();

            return;
        } catch (ExpectationFailedException $failure) {
            if (microtime(true) >= $until) {
                throw $failure;
            }

            $page->wait(0.1);
        }
    }
}

/**
 * The same tab goes to an address, however long it takes. navigate() alone gets an assertion's
 * single second per try: on a busy machine each try starts over the page the last one was loading.
 */
function sameTabTo(object $page, string $url): object
{
    return $page->navigate($url, ['timeout' => Playwright::timeout()]);
}

/**
 * The page has no serious accessibility issue (axe, critical and serious ones), as Pest's
 * own assertion checks, with room for a loaded development machine (Pest allows 5 s), once
 * what is moving (a dialog opening) stands still: faded, its colors would be misread. A
 * plan is checked as the labelled picture it is, not shape by shape (its shapes are its
 * drawing: thousands of them, and nothing to read or reach)
 */
function assertAccessible(object $page, int $seconds = 15): object
{
    $page->assertScript('document.readyState', 'complete');
    $violations = Pest\Browser\Playwright\Playwright::usingTimeout($seconds * 1000, fn () => $page->script(
        "async () => { for (let still = 0; still < 5; still++) { await new Promise(requestAnimationFrame); const moving = document.getAnimations().filter((motion) => motion.effect?.getTiming().iterations !== Infinity); if (moving.length) { still = 0; await Promise.allSettled(moving.map((motion) => motion.finished)); } } while (window.axe._running) await new Promise((wait) => setTimeout(wait, 100)); return (await window.axe.run({ exclude: [['.plan-canvas > *']] })).violations.filter((issue) => ['critical', 'serious'].includes(issue.impact)).map((issue) => issue.id + ': ' + issue.nodes.map((node) => node.target.join(' ')).join(', ')); }",
    ));

    expect($violations)->toBe([]);

    return $page;
}

/**
 * Opens a help tip and waits until its popover stands still (it moves in, and follows its
 * place as the page settles): only then can what is in it be clicked
 */
function openHelp(object $page, string $topic): object
{
    $page->click("[aria-label=\"About: {$topic}\"]");

    return standsStill($page, '.mantine-Popover-dropdown');
}

/**
 * Waits until every element a selector finds is there and stays in the same place for five
 * frames in a row: what moves in (a popover, a tour's card and its spotlight) can be measured
 * or clicked only then
 */
function standsStill(object $page, string $selector): object
{
    $selector = json_encode($selector);
    $moving = $page->script(<<<JS
        () => new Promise((settled) => {
            let last = '';
            let still = 0;
            let frames = 0;
            const look = () => {
                const found = [...document.querySelectorAll({$selector})];
                const place = found.length ? JSON.stringify(found.map((element) => element.getBoundingClientRect())) : '';
                still = place && place === last ? still + 1 : 0;
                // Ten seconds' worth of frames: what still moves by then is told, not waited for
                if (still >= 5 || ++frames > 600) settled(still >= 5 ? null : (place ? [last, place] : 'nothing found'));
                else requestAnimationFrame(look);
                last = place;
            };
            look();
        })
        JS);

    expect($moving)->toBeNull("Expected [{$selector}] to stand still, but it kept moving: " . json_encode($moving));

    return $page;
}

/**
 * Waits until a condition on the page holds, as long as assertions wait (for what Pest checks
 * only once); then it fails, saying which
 */
function waitUntil(object $page, string $condition): object
{
    $until = microtime(true) + Playwright::timeout() / 1000;

    while (! $page->script("() => Boolean({$condition})")) {
        expect(microtime(true))->toBeLessThan($until, "Expected [{$condition}] to hold, but it did not.");
        $page->wait(0.05);
    }

    return $page;
}

/**
 * Waits until the page has done what it had queued and drawn it twice: after something that
 * happened (a click handled, a clock run ahead, an answer received), the sign that what did
 * not show by then is not coming from it
 */
function settled(object $page): object
{
    $page->script('() => new Promise((done) => setTimeout(() => requestAnimationFrame(() => requestAnimationFrame(() => done(true)))))');

    return $page;
}

/**
 * From now on, the page's requests are followed, for answered() to wait for their answers
 * (again after a full load: a new page forgets)
 */
function watchRequests(object $page): object
{
    $page->script(<<<'JS'
        () => {
            if (window.requestsWatched) return;
            const watched = (window.requestsWatched = { open: 0 });
            performance.clearResourceTimings();
            performance.setResourceTimingBufferSize(100000);
            const send = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.send = function (...args) {
                watched.open++;
                this.addEventListener('loadend', () => watched.open--, { once: true });
                return send.apply(this, args);
            };
            const fetch = window.fetch;
            window.fetch = (...args) => {
                watched.open++;
                return fetch(...args).finally(() => watched.open--);
            };
        }
        JS);

    return $page;
}

/**
 * Waits until the page has had an answer since watchRequests() (to that path, when given),
 * nothing it asked for is still on its way, and what came back is drawn: the sign that what
 * an answer would have changed has not changed
 */
function answered(object $page, ?string $path = null): object
{
    $path = json_encode($path);
    waitUntil($page, "window.requestsWatched.open === 0 && performance.getEntriesByType('resource').some((entry) => {$path} === null || new URL(entry.name).pathname === {$path})");

    return settled($page);
}

/**
 * The page's clock taken over by Playwright's, its time still flowing as it does: a timer set
 * from now on is run ahead with runClock() instead of waited for. A timer set before stays
 * real: refresh the page after this for those it sets as it loads.
 */
function fakeClock(object $page): object
{
    toBrowserContext($page, 'clockInstall');

    return $page;
}

/** The page's (fake) clock run ahead, every timer due by then fired, and the outcome drawn */
function runClock(object $page, int $milliseconds): object
{
    toBrowserContext($page, 'clockRunFor', ['ticksNumber' => $milliseconds]);

    return settled($page);
}

/** A call to the page's browser context in Playwright, for what Pest does not offer */
function toBrowserContext(object $page, string $method, array $params = []): void
{
    $context = $page->page()->context();
    $guid = (fn () => $this->guid)->call($context);

    iterator_to_array(Client::instance()->execute($guid, $method, $params));
}

/**
 * Picks a day (at 00:00) on a DateTimeField, as a person does: open its calendar, click
 * the day (labelled by its date), confirm. The day must be in the month it opens on.
 */
function pickDay(object $page, string $field, DateTimeInterface $day): object
{
    return $page->click("[data-field=\"{$field}\"]")
        ->click('[aria-label="' . $day->format('j F Y') . '"]')
        ->click('.mantine-DateTimePicker-submitButton');
}
