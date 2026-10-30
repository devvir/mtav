# Architecture

How MTAV is put together: Laravel 13 (PHP 8.5) on MariaDB, and React 19 with Inertia 3 on
Mantine 9. The code is the source of truth; this guide gives its shape, so it reads
quickly. The lottery, notifications, data and delivery have guides of their own.

## Areas

| Area | Addresses | For | Code |
|---|---|---|---|
| Public | `/`, `/manual`, `/privacy`, sign-in and sign-up | anyone | `pages/Welcome`, `pages/PublicDocument`, `pages/Auth` |
| Account | `/projects`, `/notifications`, `/settings/profile` | anyone signed in | `pages/Projects`, `pages/Notifications`, `pages/Settings` |
| A project | `/projects/{project}/…` | everyone in it | `Controllers/Member`, `pages/Member` |
| Its settings | `/projects/{project}/manage/…` | its admins | `Controllers/Manage`, `pages/Manage` |
| The console | `/admin/…` | the platform's superadmins | `Controllers/Platform`, `pages/Platform` |

A project has one set of pages, the same for everyone; admins find their tools on them
(adding units, families, events; drawing the plan; running the lottery), sent to routes
under `manage/`, which are gated as a whole. What only admins have a page for (setting the
project up, invitations, the project's own settings and lists) is its **Settings**,
three tabs that are pages of their own.

Small things (a person, a family, a unit, an event, a file) open in a dialog over their
list, with the item in the address (`?person=19`, `?family=`, `?unit=`, `?event=`,
`?file=`; `lib/detailsInAddress.ts`): links open them, the back button closes them.
Dialogs that add something open from the address too (`?new=1`; `lib/addressedDialog.ts`).

## People and roles

- A **user** is one person's account. `is_superadmin` marks the platform's superadmins;
  `is_owner` the deployment's owner, its first superadmin (from `OWNER_EMAIL` and
  `OWNER_PASSWORD` while there is no account at all; until then the site shows only that
  it is under construction, but for its public documents).
- A **membership** is a user's part in one project: as an admin, in a family, or both.
- A **family** is who the lottery gives a unit to: it ranks the units of its type.

`User::canAccess()`, `manages()` and `familyIn()` answer every role question.

## Authorization

- `ResolveProject` lets into `/projects/{project}/…` only people in the project (anyone
  else gets a 404) and makes it the default `{project}` of every address built during
  the request, on the server and in the browser.
- Models inside a project resolve through it (`scopeBindings()`): another project's id
  does not exist here.
- Policies check the rest, always against the model's project. Superadmins pass every
  check but the personal ones (ranking for a family, answering an event).
- Refusals: an error page, or for a form, a message on the page it came from. A page
  left open until its form's token expired sends back to it, to try again.

## Projects

Anyone with a confirmed account starts projects, becoming their first admin. A project
is **limited** (`config/limits.php`: people, families, units, invitations a day, file
size, storage) until the platform's team makes it **unlimited**, which its admins ask
for; projects a superadmin starts are unlimited at once. Only "Limited" is ever shown.

Deleting a project (its admins, typing its name) archives it when it has a history,
else deletes it for good. Superadmins restore archived projects, or delete them for good.

## Invitations

Emailed links with a random token (only its hash is stored) that expire after two
weeks. The link opens an account, or, for an existing one, joins once signed in as the
invited address; that person is also told in the app. Admins invite into any family or
as admins; members, into their own family.

## The plan

A project's site drawn as shapes on floors (`components/plan`): units, buildings,
streets, green areas, the commons, a playground. One admin draws at a time
(`EditingLock`, renewed while the editor is open, let go after a while idle); anyone
else is told who is drawing. Editing needs a large screen and a pointer; everyone sees
the plan, and families rank from it.

## The forum

Topics with posts (`Topic`, `Post`, `ForumService`): anyone in the project opens topics
and replies; writers edit their posts; admins pin, close and remove topics. A new topic
is notified to the project, a reply to whoever wrote in the topic before.

## Frontend

- **Design system** (`resources/js/design`): the theme for each palette, the tokens
  (`--mtav-*`, `ds-*` classes) and the shared components (`Panel`, `Stat`, `Glyph`,
  `Status`, `EmptyState`, `PersonAvatar`…). Colours are chosen by measured contrast: text
  reads at 4.6:1 or more in every palette, light or dark. The UI Lab (`/admin/lab`)
  shows every component in its states.
- **Layouts** (`layouts/`): a page's folder picks its frame. A project's pages share its
  sidebar, with its Settings apart at the bottom for admins; the console has its own.
  `AppShell` is the frame: top bar (quick actions, the bell), sidebar (a drawer on
  phones), bottom tabs on a project's pages.
- **Forms** (`components/forms`) are Mantine inputs wired to Inertia's `useForm` by
  `lib/forms.ts`; dates are picked on a calendar in the person's language
  (`DateTimeField`). The server validates; every error reads the same, in the app's
  language.
- **Dialogs**: Mantine modals in pages; `confirm()` and `prompt()` (`lib/dialogs.ts`) for
  questions asked from code, typed confirmations included.
- **Addresses**: `route()` and `routeIs()` (`lib/route.ts`) work from the route list sent
  once, and compare route names, never URLs.
- **Moving between pages**: a link loads its page once the pointer rests on it; on the
  click, the sidebar lights the place at once and the page dims only if the trip is long.
- **Live updates**: `useProjectChanges({ resource: [props] })` reloads what someone else
  changed (models announce changes with `BroadcastsProjectChanges`).
- **Offline**: the service worker (`public/sw.js`) keeps each page as last loaded and
  answers with it when the server cannot be reached; the page says so and holds every
  change (`lib/offline.ts`). The app installs on devices (`/manifest.webmanifest`).
- **Help**: a (?) by every page's title (`InfoTip`) says what the page is for and offers
  "Tell me more", a tour of it (`tours/index.ts`, `lib/tour.ts`). For new people, each
  page's tour plays once by itself; any tour stops them all ("No more tours"), and
  Preferences turns them back on.
- **Words**: `_('English text')` everywhere; each language's words are keyed by the
  English (`lang/<locale>.json`), the server's in `lang/<locale>/*.php`, the manuals and
  the privacy page in `resources/views/`. Other languages are written natively, never
  translated word for word. The language is the person's choice, else their browser's.
