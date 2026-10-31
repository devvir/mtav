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
as admins; members, into their own family. Someone already in the project with no family
is added to the family at once, and told in the app and by email (`JoinedNotification`).

## The directory, guests and features

Admins may list their project (`listed`; it shows only once approved) and choose who can
join (`open_to_join`). Open, people join at once; otherwise they ask (`JoinRequest`), the
admins answer from the Invitations tab, and someone declined may ask again after
`limits.join_request_retry_days`. Open is simply approved beforehand: from there on, both are
the same.

Someone with neither a family nor a role is a **guest**, where the project `allows_guests`:
they take part in everything but ranking and inviting, and never see people's email or phone
(`MemberResource`, and searches by name only). Without guests, whoever is let in creates their
family before anything else (`ResolveProject` brings them to it, and it is all they can reach)
or someone of the family adds them. So a project without guests takes nobody new while it has
no unit types or once the lottery ran (`acceptsNewcomers`), and guests cannot be turned off
while there are any, or invitations on their way to some.

Forum, chat, gallery, files and events are on or off per project (`disabled_features`,
`ProjectFeature`; `EnsureFeature` on their routes). Off, a feature is hidden and keeps what it
holds. The lottery stays. White-label options, per deployment, will decide which of these a
project is even offered (see the plan).

**Archiving.** Removed events, files and forum posts are soft deleted (`Archivable`), and
admins see and restore them from a switch on each page (`ArchiveService`). What nobody came to
rely on goes for good: a draft event, or an event, file or topic taken back within the hour
that nothing else happened to. Families, units and unit types are deleted for good (only when
nothing depends on them, and the activity log keeps what they were): bringing one back would
not bring back its members or its place on the plan.

## The Assistant

Another conversation of the chat (`is_assistant`, one per person and project, pinned to begin
with), answered apart from the request (`AnswerInChat`, `App\Services\Assistant`). It gets words
and gives words: no tools, no internet, no database. `Prompt` writes what it knows: its rules,
the guides for the person's role and language, and a few facts about them and their project.
Nothing about anyone else goes in, so there is nothing to leak. What it answers comes in a fixed
shape, and is checked: a link names a key of `Places`, which lists only what that person can reach
(role, family, features), and the address is the server's. Short limits keep it cheap
(`config/assistant.php`): per person and deployment a day, a short history, and saying no to
either costs nothing. A limited project's people have half the messages.

The model is only for what people put in their own words. `ReadyAnswers` answers greetings, thanks
and the questions the Assistant offers, written beforehand or worked out from the project; those
take nothing from the day's messages, and work with no brain at all. Messages written in a row
are answered once. Someone's first arrival opens the conversation by itself (`users.welcomed_at`),
its greeting drawn by the page, with the page's tour offered instead of played.

It answers through a `Brain`: `ApiBrain` (the official SDK; `ANTHROPIC_API_KEY`) or `CliBrain`
(the Claude command line on a signed-in account). Failing a few times in a row, whatever the reason
(spent, down, a bad key), it rests a quarter of an hour: it asks nothing, says to try again in a
while with the guide at hand, and still gives what needs no model. With
neither it says it is not available for anything that needs the model, greets nobody, and nothing
leaves the server. A signed-in subscription account is for its owner trying things: this machine, or
dev and staging while only they use them (its token in the environment's `.env`). Production refuses
it, and anyone else using a site needs the key: Anthropic does not allow routing other people's
requests through a subscription. `php artisan assistant:try`
runs a set of real questions through the real prompt, its answers shown to be judged.

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

## The chat

Conversations between people of a project (`Conversation`, `Message`, `ChatService`), one
on one (`pair`: two people have one) or in groups with a name, for anyone with a
membership there. Only those in a conversation read it: its abilities are personal
(`ConversationPolicy`, `AppServiceProvider::PERSONAL_ABILITIES`), so neither the project's
admins nor superadmins pass them. A message reaches the others on their own channels
(`MessageSent`, `users.<id>`: where it is, each page fetches what it shows) and on their
devices (`PushMessage`), never through notifications (nothing in the bell). What each
person has read is on their place in the conversation (`read_up_to`); the sidebar counts
the conversations with something new.

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
- **Sizes**: everything is sized from the root's font size (`rem`, Mantine's too), which
  the person's text size sets (`data-text-size` on `<html>`, from the `text` cookie,
  before the first paint): Medium, the default, is 120% of the browser's, Small 100%,
  Large 140%. Only the plan's drawing keeps its own units.
- **Help**: a (?) by every page's title (`InfoTip`) says what the page is for and offers
  "Tell me more", a tour of it (`tours/index.ts`, `lib/tour.ts`). For new people, each
  page's tour plays once by itself; any tour stops them all ("No more tours"), and
  Preferences turns them back on.
- **Words**: `_('English text')` everywhere; each language's words are keyed by the
  English (`lang/<locale>.json`), the server's in `lang/<locale>/*.php`, the manuals and
  the privacy page in `resources/views/`. Other languages are written natively, never
  translated word for word. The language is the person's choice, else their browser's.
