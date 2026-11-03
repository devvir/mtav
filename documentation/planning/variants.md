# Variants

A **variant** is what a deployment is made to be. All variants are one codebase and one release
image; a deployment picks its variant with `MTAV_VARIANT`. MTAV is the variant with everything, and
what a deployment is when it names none.
Open Lottery, the first other one, is the far end: no accounts and no app behind a login, so
whatever works for it works for anything in between.

## How a variant is told apart

**A variant is a plain PHP file, `config/variants/<name>.php`.** It is versioned, reviewed and
tested like any code. There is nothing to parse: `php artisan optimize`, which each container
runs on starting, caches it with the rest of the configuration. `App\Support\Variant` answers
every question from it, in constant time.

It says:
- **modules**: the parts of the app that exist here (`accounts`, `projects`, `platform`,
  `manual`, `open_lottery`);
- **features**: each with what a project starts with (`default`) and whether a project may change
  it (`configurable`);
- **landing**: the page people arrive at;
- **privacy**: its privacy page, saying only what that variant keeps.

## Closed at the door

**Every route belongs to a module, or the build fails.** Routes declare it (`module:accounts`),
and `EnsureModule` answers 404 where the variant lacks that module: not hidden, gone. A test walks
all routes and refuses one with no module, unless it is on the short list every variant has (home,
language, health, privacy, manifest). So a new route is closed by default until someone says what
it belongs to.

**A feature is the variant's spec, then the project's choice.** `Project::hasFeature()` is the one
answer, and everything reads it: routes, navigation, the home page, settings, what buttons say.

| Spec | What it means |
|---|---|
| on, configurable | Projects start with it, and admins may turn it off |
| off, configurable | Projects start without it, and admins may turn it on |
| on, not configurable | Every project has it, and no setting appears |
| off, not configurable, or left out | It does not exist here, exactly as if it had no code |

Settings offer only what is configurable. `EnsureFeature` guards the routes, so a project with media
off, or a variant without media, refuses an upload made directly against the server. With only one
of gallery and files, the page is named after it and has no tabs. The Assistant needs the chat.

What routes do not reach follows the same answers: the route names sent to the browser leave out
closed modules, and deployment-wide duties (the owner's first account) apply only where their
module exists. Checks further in stay for rules no route can express.

## The lottery's two sides

The lottery distributes **items** among **participants**, in **groups** solved one at a time. It
works on plain ids and knows nothing of who they are. In MTAV participants are families, items are
units and groups are unit types. A **host** gives it what to decide and takes the result:
`LotteryRun::host()` is `LotteryService` for a project, `OpenLotteryService` for an open lottery.
Runs, the audit, its record and the reveal are the same for both.

## Open Lottery

A proof of concept for now, there to make us think broadly about variants and how they translate to
code. A landing page and one page per lottery. No sign-up, no login, no accounts, no email.

**The flow.** Someone creates a lottery and gets a link to share. People open it, add themselves
and rank the items. Everyone sees who is in and whether they have ranked, never what. The organizer
runs it; everyone watches it work, then sees who got what. Anyone with the link can read the
record: each person's ranking, what they got, the whole trail and its fingerprint.

### Decisions

- **Words.** The creator is the **organizer** (*owner* already means the deployment's first
  superadmin). The organizer names what is distributed ("Rooms", "Shifts"), and the page uses it.
- **Identity.** A secret kept in a cookie of that browser. Joining also gives a personal link, to
  come back on another device; creating gives an organizer link, shown until dismissed. Only hashes
  are stored. A lost organizer link cannot be recovered: nobody knows who anybody is. The lottery
  says so where the link is shown.
- **Link.** `/l/{slug}`: 10 characters, about 50 bits (the database ignores their case), so links cannot be guessed. Never listed,
  and pages ask not to be indexed.
- **Honesty about the open door.** Anyone with the link can join, under any name. That is the
  variant's nature: the organizer sees everyone before running, and removes who should not be there.
- **Names** are unique inside a lottery, ignoring case. Items too.
- **What people can change, until it runs.** Anyone joins, leaves, and re-ranks. The organizer adds
  and removes items and removes people. A removed person sees that they were removed and may join
  again; the organizer can remove them again.
- **Items changing after people ranked.** A removed item leaves their rankings. A new one is not
  ranked by anyone, and is pointed out to those who had ranked. Nobody's "ranked" mark is taken away.
- **Running needs** at least 2 people and 1 item. The organizer alone runs it, and confirms what is
  unusual, naming it: who has not ranked (they follow an order fixed at random, or the organizer
  removes them first), and how many people get nothing or how many items stay left over when the
  counts differ.
- **Once.** A lottery runs one time. No undoing and no running again, or an organizer could repeat
  it until pleased. A run that fails to solve may be tried again: it decided nothing. Wanting another
  means creating another.
- **The organizer may take part** by joining like anyone else; nothing else changes.
- **Rankings become public when it runs,** since checking the result needs them. Joining says so
  in one line.
- **Everyone is told by the page.** Open pages update live; a page that lost the connection catches
  up within seconds, by asking again. There is nothing to notify anyone else with.

### Limits, since anyone may write

- 50 people and 100 items per lottery: the sizes the solver answers quickly.
- Creating is limited per address, as are joining and ranking.
- A lottery nobody touched for 90 days goes, with its people and record; each page says when.
- Names of at most 60 characters, and lists are capped, so a page stays small.
- Nothing can be uploaded, and no links are made from what people write.

### Before it goes live

- Its own name (`APP_NAME`), and an environment on the server. Its address is `sorteo.mtav.uy`, on the
  environment ports 8003 and 8083.

### Out of it, for now

Moving the page data and the cookie identity out of its controller, and comparing it with MTAV's
lottery for code to share. Deadlines, identical items in quantity, several items per person, choosing groups inside a
lottery, a repeatable tie-break for recomputing a result, a lighter JavaScript bundle (the variant
ships the whole app's pages today).
