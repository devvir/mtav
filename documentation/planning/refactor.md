# Refactor plan

A staged refactor of MTAV towards a solid, efficient app with a clean, friendly UI.
Nothing is in production yet: schemas, auth flows and APIs change freely, with no
backward compatibility. The stack stays (Laravel + Inertia + Vue); any module may be
rewritten from scratch where that is the better path. The work runs autonomously; every
decision taken on the way is listed below for review.

## Business rules (fixed)

- **Superadmins** add admins and users, create projects, and can do anything an admin can.
- **Admins** can do what members do, plus configure the projects they manage (0 or more):
  families, member invitations, unit types, units, the project plan, events, the lottery.
- **Members** belong to a family. They upload media (images, video, audio, documents),
  rank unit preferences on behalf of their family, and invite people to their own
  family only.
- **Lottery**: assigns each unit to a family from the families' preference rankings
  (fairness first, then overall satisfaction). Preferences lock when execution starts.
  Each family sees only its own preferences. Every execution is audited.
- **Events**: published or draft, optional RSVP; the lottery is itself an event that
  exists from project creation.

## Decisions (agreed with the author)

- **Per-project roles.** One account per person. A person can take part in any number
  of projects with a different role in each. Superadmin is a platform-level role.
- **Open sign-up.** Anyone can sign up and create projects. A new project starts
  *unapproved*, with limits on anything open to easy abuse (media storage and file
  sizes, invitation counts and rate, lottery size, …). A superadmin approval — a
  statement of trust in the project's managers, which they request — lifts the limits.
  Projects created by a superadmin start approved. Every limit is configurable; nothing
  is removed, only bounded.
- **Lottery invalidation** moves to admins, with mandatory visibility: invalidating
  requires a reason, and every member of the project sees that the lottery was
  invalidated, by whom, when and why.
- **Features come out, whole (white-label).** Other deployments are MTAV with parts taken
  out, never new features: everything MTAV needs is built, and nothing is coupled so
  that it cannot be removed. What every version keeps is the core — people (members and
  admins) state their preferences, and the lottery distributes the items of one set to
  them. In MTAV the items are units and the participants families; elsewhere the items
  may be anything, and the groups may be called otherwise or be absent (then each
  person is a group of one, and groups are never shown). Units, unit types and the plan
  are one module, in or out together; so are events, files and invitations by members.
  To be designed in full once MTAV works; until then, no new coupling between modules.
- **White-label, later (nice-to-have).** The deployment decides the vocabulary and
  shape of the grouping concept. Internally the lottery participant is the group, not
  its users. MTAV calls the group a *family*; another deployment may name it
  differently, or hide it entirely (each user is silently a group of one). Until then
  the code keeps families, but avoids assumptions that would block this.
- **One project UI; admins see more tools.** A project has one set of pages, the same
  for everyone: families, units with their plan, events, media, the lottery, people.
  Admins see the same pages with their tools on them (create, edit, remove, invite, run
  the lottery, edit the plan in place); there are never two flows holding the same
  things, one read-only and one not. Pages with no member counterpart (the project's
  settings, invitations, the setup overview) sit in an admin group of the same sidebar.
  Details of simple things (a person, a family, a unit, an event, a file) open as dialogs
  over their list, with the item in the URL. The platform console (`/admin`) stays apart,
  for superadmins.
- **React frontend.** The frontend moves from Vue to React 19 on Inertia's React adapter,
  built on Mantine and Tabler icons, starting from the author's jobhunter design system
  (`@jobhunter/design` and its UI Lab) copied in as MTAV's own design package with its
  own palette and brand. Done after the dependency upgrade is green and committed, before
  the parity check and dashboards. The browser suite is framework-agnostic and guards the
  rewrite. The thesis's stack chapter is updated at the end, with a note.
- **Latest everything.** Every dependency moves to its latest major: PHP 8.5, Laravel 13,
  Inertia 3, Pest 5, Vite 8, Node 26, MariaDB 12.3 (LTS), nginx 1.30, pnpm 12, Mailpit
  in place of the abandoned MailHog. TypeScript waits at 6 until vue-tsc supports 7 (moot once the frontend is React).
- **Delivery from scratch.** Building, testing and deploying are designed anew; nothing
  of the earlier deployment (its scripts, images or host setup) is kept by default. Every
  push and pull request runs the whole test suite on GitHub Actions and builds one image
  per commit (GitHub's registry); every environment runs those images, nothing is built
  on a host. A green `main` deploys staging by itself; dev is deployed by hand from any
  branch (optionally from fresh demo data); production by hand, from `main` only, with
  the image staging already ran. Deploys are triggered on GitHub (its "Run workflow"
  dialog, the command line, or the console below); only the step that reaches a host is
  specific to today's single server, so moving to a cloud provider replaces that step
  alone. The author is present for the first staging deploy. Design in
  `documentation/technical/delivery.md`.
- **Deployment tools in the console.** The platform console gets a Deployments page: what
  each environment runs against `main`, deploying dev from a branch, production from
  `main`, and an earlier image again. It is shown only to superadmins whose email is
  listed in the environment's settings (`DEPLOYERS`), so granting superadmin never
  grants deploying; an empty list hides it. The app asks GitHub to run the deploy (a
  token limited to this repository's workflows), so it works the same wherever the
  environments live.
- **Mail per environment.** Staging and production send real mail; staging (and dev, if
  it sends) skips addresses under blocked domains, logged instead: by default the
  reserved ones every demo account uses (`example.com`, `.org`, `.net`, and the `.test`,
  `.example`, `.invalid`, `.localhost` names), set per environment. Dev keeps a catcher.
- **Full browser coverage.** The browser suite covers every button, every form and every
  edge case; anything later judged too slow or costly for every run moves to an
  on-demand run, by decision.
- **Assistant (optional module).** A chat in the app that guides people: it explains
  how to do things and MTAV's rules from documentation written for it, and acts in the
  page through tools (start a guided tour, go to a page, later more), run by the browser
  as the signed-in user, so it can never do what that person could not. Its
  documentation and tools are assembled from the modules a project has, like everything
  else white-label. The model is a setting, chosen by measured answers on real
  questions; it runs on an API key with a spending cap, the documentation cached.
- **Contact details, shared by choice.** Each person decides, beside their email and
  their phone in the profile, whether the people of their projects see it. Shared, it
  shows on their card and searches find it; not shared (the default), only admins see it
  and find people by it.
- **Projects are archived, not lost.** An admin deleting a project with meaningful
  activity (people invited, the lottery run, …) archives it: to them it is gone, but it
  stays for audit. A fresh project with no activity is deleted outright. Superadmins see
  archived projects and delete them for good, after confirming a summary of what goes
  (members, families, units, files, how long it was active, its lottery).
- **Granting the superadmin role is rare.** It has a place of its own in the platform
  console, never a button offered on every account.
- **Demo data covers every kind of file**: the seeder shares photos, videos, audio and
  documents, so every viewer and list can be tried without uploading.
- **Quick actions**: a "+" menu in the top bar with what the person may do (invite,
  share files, create an event, …), as a shortcut: every action stays where it lives.
- **Institution line**: a line under the brand naming the institution ("FING ≒ UDELAR"),
  set per deployment.
- **Local first.** A project's data, as its members see it, is small (rarely more than a
  couple of hundred people), so the app keeps it on the device and works from there:
  - lists of up to 200 items come whole and are searched, filtered and sorted in the
    browser, with no request per keystroke (past 200, the server pages and searches, as
    before);
  - a project snapshot (everything the person may see, with its version) is fetched once
    and kept on the device (IndexedDB, per account and project, cleared on signing out);
  - pages render from it at once and ask the server in the background: offline, the
    local data stays (and the failure is logged); unchanged, nothing happens; changed,
    the screen and the local copy update;
  - the live change events (Reverb) update the local copy as they arrive;
  - it only ever holds what that person may see; a change of their role rebuilds it.
- **Thesis parked.** The tutor already reviewed the published draft. It is updated at
  the very end, from the tutor's review, with notes marking each change.

## Decisions (taken autonomously, for review)

- **Refactor in place**, not a rewrite in another stack.
- **Memberships.** `memberships(user, project, family?, is_admin)`, one per user and
  project. An admin may also belong to a family of the same project (a cooperative's
  admin is often a cooperativist too); a member without `is_admin` must have a family.
  `users.is_superadmin` replaces the `SUPERADMINS` env list (granted with an artisan
  command). The Admin/Member model subclasses go.
- **Project in the URL** (`/projects/{project}/families/…`) instead of a "current
  project" cookie: links are shareable, tabs independent, notification links exact.
  Child models are resolved through the project (scoped bindings), and policies check
  membership explicitly — no global project scopes, no call-stack inspection.
- **Invitations are records** (`invitations`: project, family?, admin flag, email,
  hashed token, inviter, expiry, acceptance). Accepting one creates the account or, for
  an existing account, adds the membership. The token is no longer the password.
- **Sign-up** with email verification. A user with no projects lands on a "your
  projects" page: pending invitations, and creating a project.
- **Lottery runs.** Each execution is a `lottery_runs` record (running, completed,
  failed, invalidated — with who, when and why). Preferences lock while a run is running
  or completed. The lottery event keeps only the schedule and description.
- **Private media.** Files live on a private disk and are streamed by a route that
  checks project access. SVG is not accepted.
- **GLPK is the default solver**; random only when chosen explicitly.
- **Forms:** hand-written per entity (`components/forms`) on shared field components
  (`components/form`); validation lives in one FormRequest per entity. FormService and
  the two `devvir/*` packages are gone. (The author proposed generating forms and
  validation from our own entity definitions; still open to revisit.)
- **Modals:** our own `Modal` (Reka's Dialog primitive; a bottom sheet on phones) and
  `confirm()` / `prompt()` dialogs called from code.
- **Live updates are best effort:** a websocket server that is down never fails an action.

## Work order

1. **Use-case tests** (`tests/UseCases/`, see technical/testing.md). Tests that fail on a
   known bug go in the `bug` group, decided-but-unbuilt requirements in `new`. Old
   fixture-based tests are deleted once covered.
2. **Domain core**: new schema (fresh migrations), models, memberships, invitations,
   sign-up, project approval and limits, lottery runs, authorization, project-scoped
   routes. Scenario builders follow; the use-case suite goes green.
3. **Real-time**: notifications broadcast to their channel; resource changes broadcast
   to the project channel; open pages refresh what changed.
4. **Frontend, resource by resource**: own modal system (on Reka's Dialog primitive)
   replacing InertiaUI Modal; forms per entity on shared field components (including a
   dedicated unit type form); pages adapted to the new routes and props.
5. **Plan editor**: multiple floors, resizing, polygons, non-unit items.
6. **Guided help**: info icons (?) explaining concepts where needed, ending, where it
   fits, with "Show me how", which starts a guided tour of that spot.
7. **Polish**: containers, documentation rewritten against the code, E2E journeys
   rewritten.
8. **Parity with the pre-refactor app** (`452ca64~1`, at stg.mtav.uy with the same demo
   logins): what it had that the new app lacks, each to keep or drop by decision. Seen
   so far (home pages as member and admin, menus):
   - **Home**: counts of units, families, members, admins, media, documents and events;
     a stacked preview of the gallery; the families with their marks; the admins; the
     latest members; every unit by type. The new home has the lottery, three counts, what
     is coming and recent files. *Recommendation*: bring families, admins and latest
     members to the home (item 9); units by type already live on the units page.
   - **Quick actions**: a "+" menu in the top bar (create a member, upload media, upload
     documents, an event). *Recommendation*: keep, as a "+" menu of what the person may
     do, the same on every page.
   - **Projects** (several projects): cards with families and members counts, a search
     and an "active / all" filter. *Recommendation*: check against Your projects, add the
     counts and the filter if missing.
   - **Branding**: "FING ≒ UDELAR" under the logo. *Recommendation*: an institution line
     under the brand, set per deployment (white-label).
   - **Links**: the documentation and the GitHub repository in the sidebar (the "Dev
     Dashboard" is a development tool, not kept). *Recommendation*: link the repository
     from the guide, since the code is published with the thesis.
   Still to compare: each page's details, page by page, for admins.
9. **Dashboards**, at least as rich as the old one, shaped per audience: the project's
   home (the lottery, what is coming, recent files, the families and people, and for
   admins the setup checklist until done), the setup overview (progress, what needs
   attention, limits), the platform console (projects awaiting approval, activity).
10. **The plan, richer** (the author's ideas; units, types and the plan already share one
    page, where admins edit the plan in place). A unit added anywhere appears on the plan,
    waiting beside the canvas to be placed. Each type may carry a base shape and size, so
    a new unit of that type starts as that shape (plain squares when none is set). Floors:
    the floors just below and above show faintly behind the current one (onion skinning),
    and a unit's edges snap to those of the units under it. Look for an established editor
    or library before building more by hand. The plan tells people what is what and where,
    because they choose by it (near the playground, the parking, the common spaces; far
    from the street): besides the boundary, streets, green areas and common spaces it has
    entrances, parking and playgrounds; parks are drawn with trees (more as they grow);
    common spaces look clearly unpickable; housing units carry a subtle house mark,
    recognisable at first sight. The plan views fit what is drawn (no empty canvas
    around it). Attractive and legible first, then precise.
11. **Visual design pass**, last — once everything works. A modern look across every
    theme: glossy, semi-transparent cards; sticky bars with backdrop blur; soft light on
    what is interactive; plenty of icons; badges with a status dot; motion for what comes
    and goes. A new look, not the old one; ideas worth keeping from it: generated avatars
    per family, dense stat tiles, a stacked photo preview, hero headers, quick actions.
    High contrast and reduced motion keep their own, plainer rendering. It starts from the
    jobhunter design system (see *React frontend*) and is built as a
    **component library** first: design tokens (pages never name a colour) and every
    shared component — buttons, chips, badges, fields and dropdowns, cards, stat tiles,
    tables, avatars — composed from smaller ones, so nothing on a page is one-off. A **UI
    Lab** page (superadmins, in the platform console) shows each component in every
    state and variant (base, hover, pressed, keyboard focus, disabled), switchable across
    themes and modes.
12. **Delivery** (see *Delivery from scratch*): the pipeline, its documentation, the
    staging deploy (first run with the author), the approval-gated production deploy.
13. **Full browser coverage** (see the decision): every button, form and edge case.
14. **Assistant** (see the decision), after the queue above: agent documentation, the
    chat, its tools, a measured model choice.
