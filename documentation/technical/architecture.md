# Architecture

How the application is put together. The code is the source of truth; this guide
explains the shape so the code reads quickly.

## One project UI, a platform console

| Area | URLs | For | Controllers / pages |
|------|------|-----|---------------------|
| Project | `/projects/{project}/…` | everyone taking part in a project | `Http/Controllers/Member`, `pages/Member` |
| Admin tools | `/projects/{project}/manage/…` | the project's admins (and superadmins) | `Http/Controllers/Manage` |
| Platform console | `/admin/…` | superadmins | `Http/Controllers/Platform`, `pages/Platform` |

A project has one set of pages, the same for everyone. Admins see their tools on them:
the units, events, families, people and lottery pages give admins creating, editing and
removing (in dialogs, which a link opens with `?new=1`, `?new_type=1` or `?new_unit=1`),
drawing the plan in place, and running the lottery. What admins do is sent to the admin
routes under `manage/`, gated as a whole.

Simple things have no page of their own: a person, a family, a unit or an event opens in
a dialog over its list, with the item in the address (`?person=19`, `?family=`, `?unit=`,
`?event=`; `lib/detailsInAddress.ts`). Opening and closing are visits that reload only
that prop, so links open them and the back button closes them; their old addresses
(`people/19`, …) redirect there, and saving one returns to it. The few pages with no
counterpart for members
sit under `manage/` too (`pages/Manage`): the setup overview, invitations and the
project's settings.

Outside the areas: accounts (sign-up, login, password, email confirmation), settings,
notifications, the list of one's projects, and invitation links.

## People and roles

- A **user** is one account per person (`users`). `users.is_superadmin` marks the
  platform's superadmins (`php artisan mtav:superadmin {email}`).
- A **membership** is a user's part in one project (`memberships`): as an admin
  (`is_admin`), as a member of a family (`family_id`), or both. One per user and project.
- A **family** is the lottery participant: it ranks the units of its type and gets one.

`User::canAccess($project)` (taking part, or superadmin), `User::manages($project)`
(admin, or superadmin) and `User::familyIn($project)` answer every role question; a
user's memberships load once per request.

## Authorization

- `ResolveProject` (routes under `/projects/{project}`) lets in only people taking part
  in the project — anyone else gets a 404 — and makes the project the default
  `{project}` of every URL generated during the request (frontend included, via Ziggy).
- Child models resolve through the project (`scopeBindings()`): a family id of another
  project does not exist under this one.
- The admin routes are gated as a whole (`can:update,project`); policies check the rest,
  always against the model's project. Superadmins pass every check
  (`AppServiceProvider`), except the personal ones that need a family (ranking units,
  answering an event).
- Denials answer 403 (404 outside one's projects). Inertia visits get an error page, or
  a flash message for a refused form submission (`bootstrap/app.php`).

## Projects and approval

Anyone with a verified account starts projects (`ProjectService::create`), becoming
their first admin. A project starts **unapproved** and works within the limits of
`config/limits.php` (people, invitations a day, units, families, file sizes, storage,
lottery size). Its admins ask a superadmin to approve it; approval lifts the limits.
Projects started by a superadmin are approved at once. `Project::limit($key)` returns a
limit, or null for approved projects.

## Invitations

`InvitationService` creates an invitation (`invitations`) and emails a link with a
random token; only its hash is stored. Invitations expire
(`limits.invitation_days`). The link creates the account (the person picks a password)
or, for an existing account, adds the project once they sign in as the invited address.
Signed-in people also answer pending invitations from their projects page.

## The lottery

- Each family **ranks** units of its type (`PreferencesService`). Units it does not
  rank follow in an order random but fixed per family. Reading a ranking never writes.
- An admin **runs** the lottery once it is due (`LotteryService::run`): this creates a
  `lottery_runs` row (status `running`), records what the run decides from (the
  manifest: every family's full ranking per unit type) and dispatches `RunLottery`.
- `RunLottery` solves it (`LotteryOrchestrator` → the configured solver; GLPK by
  default) and writes the whole assignment in one transaction (`completed`), or records
  why it failed (`failed`, the lottery can run again).
- While a run is running or completed, rankings and the schedule are locked.
- An admin may **invalidate** a completed run, giving a reason: assignments are cleared,
  the run becomes `invalidated` (who, when, why — shown to everyone in the project) and
  the lottery can run again.
- Every step is kept in `lottery_audits`, never deleted.

The solvers (`Services/Lottery/Solvers`) work on plain data (`LotteryManifest`,
`LotterySpec`), never on models.

## Files

Project files live on the private `media` disk and are served by `media.file` /
`media.thumbnail`, behind `ResolveProject`. Types are checked by content
(`MediaService::MIME_TYPES`; no SVG). Avatars are public.

## Notifications

`NotificationService::send` stores a notification for one user (private), a project, or
the superadmins (global), and broadcasts it (`NotificationSent`) on `users.{id}`,
`projects.{id}` or `superadmins` (`routes/channels.php`). People see notifications from
their registration on; read state is per user.

## Frontend

React 19 + Inertia, on Mantine (`@mantine/core`, `dates`, `dropzone`, `notifications`),
Tabler icons and dnd-kit for dragging.

- **Design system** (`resources/js/design`): the Mantine theme for each palette, MTAV's
  tokens (`--mtav-*`, classes `ds-*`) and shared components (`Panel`, `Stat`, `Glyph`,
  `Status`, `EmptyState`, `PersonAvatar`, `FamilyMark`, `FileDrop`…). Colours are chosen
  by measured contrast (`theme/contrast.ts`): every text and filled background reads at
  4.6:1 or more in every palette, light or dark, standard or high contrast. `Appearance`
  keeps the palette, scheme and contrast in cookies, read before the first paint. The UI
  Lab (`/admin/lab`, `resources/js/lab`) shows every component in its states.
- **Layouts** (`layouts/`): a page's folder picks its layout. `Member/…` and `Manage/…`
  pages share `ProjectLayout` (the project's places, plus an "Administration" group for
  admins), `Platform/…` the console's, `Auth/…` the sign-in one; the rest is the account
  area. `AppShell` is the frame: top bar with the notifications bell, sidebar in groups
  (drawer on phones), bottom tabs on a project's pages.
- **Forms** are hand-written per entity (`components/forms`) on Mantine inputs, wired to
  Inertia's `useForm` by `lib/forms.ts`. A field's short hint sits beside its label, so
  fields side by side stay in line; required fields carry an asterisk, and `FormActions`
  says so beside the buttons. The server validates; forms are `noValidate`, so every
  error reads the same, in the app's language.
- **Dialogs**: Mantine modals in pages, and `confirm()` / `prompt()` from
  `lib/dialogs.ts` for questions asked from code (typed confirmations included),
  rendered by `DialogHost`.
- **Routes**: `route()` (`lib/route.ts`) works from the route list sent once per session
  (the `routes` prop) and each visit's `ziggy` prop, so the current project is the
  default `{project}`.
- **Live updates**: `useProjectChanges({ resource: [props to reload] })` reloads a page's
  props when someone else changes them; `useLiveNotifications()` (in the shell) shows
  notifications as toasts. Both need `VITE_REVERB_APP_KEY`.
- **Guided help**: `InfoTip` (a ? beside what needs explaining) ends, where a tour exists,
  with "Show me how", which plays a tour (`tours/index.ts`; `lib/tour.ts`): steps point
  at `data-tour="…"` marks, missing ones are skipped; `TourHost` (in the shell) dims the
  page and lights up each step's element.
- **Copy**: `_('English text')` everywhere; `lang/es_UY.json` holds the Spanish, keyed by
  the English (the English file is empty on purpose).
