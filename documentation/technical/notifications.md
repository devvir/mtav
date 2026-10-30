# Notifications

How people learn that something happened: the bell, a toast on the open page, a
notification on their devices, and email.

## What is notified

| What | To whom |
|---|---|
| The lottery done, or invalidated (with who and why) | everyone in the project |
| A lottery run that failed | the admin who ran it |
| Someone joined (a family, or the project) | everyone in the project |
| An invitation, for someone who already has an account | that person |
| A new forum topic | everyone in the project |
| A reply in a forum topic | whoever wrote in it before |
| A project started, or asking to be unlimited | the platform's superadmins |
| A project made unlimited | everyone in it |

Chat messages are not notifications: they reach the others live and on their devices, and
the sidebar counts what is unread ([architecture.md](architecture.md)).

People see notifications from when their account began; an archived project's go with
it. Whoever caused one gets no push for it.

## Stored once, written per reader

`NotificationService::send` stores one notification for a person (`private`), a project
(`project`) or the superadmins (`global`). It keeps its words as translation keys with
their values (`lang/<locale>/notifications.php`), so each reader reads it in their own
language: the bell (`NotificationResource`), the toast and the push all write it out for
whoever receives it. Read state is per person.

## Live, on the open page

`NotificationSent` is broadcast through Reverb on `users.{id}`, `projects.{id}` or
`superadmins` (`routes/channels.php`, each checking access). It carries only which
notification it is: the page fetches it in its reader's language and shows it as a
toast, and the bell's count follows. A websocket server that is down never fails what
caused the notification.

## On devices, with the app closed

Web Push, as browsers define it: free, no account anywhere, carried by each browser's own
push service (Google's, Mozilla's, Apple's), encrypted for that browser.

- The deployment has a pair of VAPID keys (`./mtav artisan push:keys` writes them for
  `.env`: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`). Without them, nothing is offered.
- A person turns it on per device, in their profile (Preferences): the browser asks them
  then, never on arrival. On iPhone and iPad, from the app added to the home screen.
  The device is kept in `push_subscriptions`; turned off, it goes.
- Each notification queues `PushNotification`, which sends it to every device of every
  recipient, in their language (`Services/Push`). A device its browser dropped is
  forgotten.
- The service worker (`public/sw.js`) shows it, and a click opens what it is about, in
  the app's window if one is open.

## Email

Account mail (confirming an address, resetting a password, an email change), invitations,
the confirmation of a place someone now has (accepted into a project, added to a family),
and the owner's notice of a new error (see [data.md](data.md)) go by email, in the
recipient's language (`lang/<locale>/emails.php`). Addresses under the domains in
`mail.blocked_domains` (on staging, the demo accounts' made-up ones) are skipped
(`Listeners/SkipBlockedRecipients`).
