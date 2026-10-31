# Variants

A **variant** is what a deployment is made to be. All variants are one codebase and one release
image; a deployment picks its variant with `MTAV_VARIANT`. MTAV is the variant with everything.
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
- **features**: which of a project's features may exist, so which settings admins are offered;
- **landing**: the page people arrive at.

## Closed at the door

**Every route belongs to a module, or the build fails.** Routes declare it (`module:accounts`),
and `EnsureModule` answers 404 where the variant lacks that module: not hidden, gone. A test walks
all routes and refuses one with no module, unless it is on the short list every variant has (home,
language, health, privacy, manifest). So a new route is closed by default until someone says what
it belongs to.

**A project's features merge the two levels in one place.** `Project::hasFeature()` is true only
if the variant offers the feature and the project has it on. `EnsureFeature` guards the routes
with it, so a project with media off, or a variant without media, refuses an upload made directly
against the server.

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

A landing page and one page per lottery. No sign-up, no login, no accounts, no email.

**The flow.** Someone creates a lottery and gets a link to share. People open it, add themselves
and rank the items. Everyone sees who is in and whether they have ranked, never what. The organizer
runs it; everyone watches it work, then sees who got what. Anyone with the link can read the
record: each person's ranking, the place they got, and the fingerprint.

### Decisions

- **Words.** The creator is the **organizer** (*owner* already means the deployment's first
  superadmin). The organizer names what is distributed ("Rooms", "Shifts"), and the page uses it.
- **Identity.** A secret kept in a cookie of that browser. Joining also gives a personal link, to
  come back on another device; creating gives an organizer link, shown until dismissed. Only hashes
  are stored. A lost organizer link cannot be recovered: nobody knows who anybody is. The lottery
  says so where the link is shown.
- **Link.** `/l/{slug}`: 10 characters, about 60 bits, so links cannot be guessed. Never listed,
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

### Out of it, for now

Deadlines, identical items in quantity, several items per person, choosing groups inside a
lottery, a repeatable tie-break for recomputing a result, a lighter JavaScript bundle (the variant
ships the whole app's pages today), and an environment of its own on the server: that needs a
domain, and a deployment set up with `MTAV_VARIANT=open_lottery`.
