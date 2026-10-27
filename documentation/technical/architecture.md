# Architecture

How the application is put together. The code is the source of truth; this guide
explains the shape so the code reads quickly.

## Three areas, one domain

| Area | URLs | For | Controllers / pages |
|------|------|-----|---------------------|
| Member app | `/projects/{project}/…` | everyone taking part in a project | `Http/Controllers/Member`, `pages/Member` |
| Project admin | `/projects/{project}/manage/…` | the project's admins (and superadmins) | `Http/Controllers/Manage`, `pages/Manage` |
| Platform console | `/admin/…` | superadmins | `Http/Controllers/Platform`, `pages/Platform` |

Outside the areas: accounts (sign-up, login, password, email confirmation), settings,
notifications, the list of one's projects, and invitation links.

All three areas share the models, services and policies. They differ in routes,
controllers, layouts and pages, each shaped for its audience: the member app shows
units, families and the plan read-only; the admin area edits them.

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
- The admin area is gated as a whole (`can:update,project`); policies check the rest,
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
