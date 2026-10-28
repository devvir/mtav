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
7. **Polish**: containers and deployment, documentation rewritten against the code, E2E
   journeys rewritten.
8. **Parity check** against the pre-refactor app (`452ca64~1`, still running at
   stg.mtav.uy with the same demo logins), page by page: nothing it did is lost without a
   decision.
9. **Dashboards**, at least as rich as the old one, shaped per audience: the member app's
   home (the lottery, what is coming, recent files, the families and people), the admin
   overview (setup progress, what needs attention, limits), the platform console (projects
   awaiting approval, activity).
10. **Units, types and the plan as one page** (the author's idea): "Units" holds the
    types, the units and the plan together, and the plan is the units' visual aid, never
    a drawing apart. A unit added anywhere appears on the plan (beside the canvas, waiting
    to be placed); a unit added on the plan asks for its type and identifier. Each type
    may carry a base shape and size, so a new unit of that type starts as that shape
    (plain squares when none is set). Floors: the floors just below and above show
    faintly behind the current one (onion skinning), and a unit's edges snap to those of
    the units under it. One plan, in one place: the plan people see is the one admins edit,
    switching it to editing in place (admins only), never a separate page to find. Look for
    an established editor or library before building more by hand. The plan tells people what is what and where, because they choose by it
    (near the playground, the parking, the common spaces; far from the street): besides
    the boundary, streets, green areas and common spaces it has entrances, parking and
    playgrounds; parks are drawn with trees (more as they grow); common spaces look
    clearly unpickable; housing units carry a subtle house mark, recognisable at first
    sight. Attractive and legible first, then precise.
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
