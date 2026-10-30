# Desktop notifications

What it would take for MTAV's notifications (the bell) to reach people outside the page,
as the system's own notifications, on computers and phones. Not built.

## Two steps, both free

1. **While a tab is open, even hidden**: the browser's Notification API. The app already
   receives each notification live (Reverb); when the tab is not in view, it also shows a
   system notification, once the person allowed it. No server work, no service, no cost:
   a permission switch in the profile and a few lines where live notifications arrive.
   Reaches only people with a tab open.

2. **With no tab open**: Web Push. The browser's own push service delivers it (Google's for
   Chrome and Edge, Mozilla's for Firefox, Apple's for Safari, iPhone included once the
   site is added to the home screen). Free for any volume: no account, no paid service.
   It takes:
   - a pair of VAPID keys (generated once per deployment, kept in its `.env`);
   - a service worker (a small script the browser keeps) that shows what arrives;
   - a table of each person's subscriptions (one per browser they allowed);
   - sending through a free Laravel channel (laravel-notification-channels/webpush),
     queued like the mail;
   - a permission switch in the profile, asked for when the person turns it on (never on
     arrival: browsers block sites that ask at once).

   About two to three days of work, with tests; the cost stays zero. Each notification
   would then be written in its reader's language, which already needs notifications
   stored as data rather than text (planned, low priority).

## What it does not need

No Firebase project, no paid push provider, no native app. Email stays as it is.
