<?php

use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

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
