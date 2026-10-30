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
  is removed, only bounded. The limits keep abuse in check, never push anyone to ask: by
  default 200 people, 50 families and 50 units, 150 invitations a day, files of 25 MB
  and 500 MB in all (asking for unlimited is free and easy) (`config/limits.php`).
  - **As people read it**: "approved" and "superadmin" stay internal (the console). A
    project is *Limited* ("Limitado") or *Unlimited* ("Ilimitado"), a fact either way;
    whoever runs the platform is "the {app} team" ("el equipo de MTAV"), named after the
    deployment. The setup page keeps a limited project's room folded behind a quiet
    "Limited" button, where it asks for unlimited; no banner on every page.
  - A project's organization is free text, suggesting the federations of Uruguay's
    housing cooperatives (FUCVAM, FECOVI); any other name, or none, is as good.
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
  on a host. Deploying is pushing: each environment follows a branch of its own, `dev` →
  dev, `staging` → stg, `main` → www, and a green push deploys it by itself. Commits
  move up by fast-forward only (`staging` to `main` always, `dev` to `staging` as fits),
  so production runs the very commit, and image, staging ran; `main` takes no commit of
  its own. The tools to deploy otherwise stay (another branch on dev, an earlier image,
  a rollback), triggered on GitHub (its "Run workflow" dialog, the command line, or the
  console below); only the step that reaches a host is specific to today's single
  server, so moving to a cloud provider replaces that step alone. Each environment has
  a deploy key of its own, its secrets limited to its branch, and the host's gate
  takes an image only for the environment of its branch. First dev and staging by
  pushing; production follows once the app is more polished. Still open, to settle with
  the author near the end: how rollbacks stay safe with branches that only move forward
  (a deployment concern, perhaps). Design in `documentation/technical/delivery.md`.
- **Deployment tools in the console** (built). The platform console has a Deployments page: what
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
- **Languages**: each person picks theirs (Preferences), for the app and its mail; without
  a choice, their browser's when the platform has it, else English. English, Spanish, French, Portuguese (Brazil) and Italian,
  each written natively. Adding one is its line in `config/app.php` with its words and
  guides; tests refuse a language with a word, a blank or a guide missing.
- **Demo data to try and to show every feature**: three projects at three stages (ranking
  its units, its lottery run, being set up), with sites drawn floor by floor, families
  of Uruguayan names, events people answered, forum topics, pending invitations, and
  files of every kind, so nothing needs creating first.
- **Chat**: the people of a project talk one on one or in groups. Only those in a
  conversation read it, the project's admins and superadmins included (the forum is for
  what concerns everyone). Messages reach the others live and on their devices, never in
  the bell; the sidebar counts the conversations with something new.
- **Directory of projects**: admins choose whether their project is listed (it shows only
  once approved: a project nobody vetted is not shown to everyone) and who can join: anyone
  at once, or those they accept (a declined person may ask again after 30 days). It is
  reached from the project selector. Whoever joins this way enters with no family, and
  creates theirs, or someone of their family adds them: being in the project already, they
  enter that family at once, told in the app and by email (no invitation to accept).
- **Next, agreed in outline**:
  - *Who can do what, and project types*: most features on or off per project, and
    predefined types of project that set them all at once; the housing cooperative
    (MTAV) is the type with everything.
- **Text size**: each person picks Small, Medium or Large (Preferences, kept on the
  device, there from the first paint); everything is sized from it (the root's font size,
  sizes in rem). Medium, the default, is a fifth larger than the browser's own: many
  people of a cooperative read the app without glasses or a large screen.
- **Media**: the gallery holds photos and videos; recordings sit with the documents, in
  Files, as the project's records (minutes read aloud, a committee's message), each
  playing in its row (pause, position, speed) without opening it.
- **Quick actions**: a "+" menu in the top bar with what the person may do (invite,
  share files, create an event, …), as a shortcut: every action stays where it lives.
- **Institution line**: a line under the brand naming the institution ("FING ≒ UDELAR"),
  set per deployment.
- **Local first.** A project's data, as its members see it, is small (rarely more than a
  couple of hundred people), so the app keeps it on the device and works from there:
  - lists of up to 200 items come whole and are searched, filtered and sorted in the
    browser, with no request per keystroke (past 200, the server pages and searches, as
    before);
  - each page is kept on the device as it was last loaded (by a service worker,
    `public/sw.js`: the page as the server answers it, never a second copy of the data
    in another shape), and a project's main places are loaded in the background after
    arriving: offline they open, even unvisited, and so does a reload; the server
    answers first whenever it can (it is always the newest), the kept copy when it
    cannot;
  - offline, the bar says so, and every change (adding, editing, uploading, removing,
    ranking, answering an event) is held with a clear message, never shown as done;
  - what is kept is the person's own: another person signing in on the device starts
    afresh;
  - *still open*: pages drawn from the device at once, before the server answers (a store
    of the project's data that each page reads); measured worth it before built, as it
    reshapes every page.
- **An owner per deployment.** A deployment is used only once it has an owner, its first
  superadmin: while it has no account at all, `OWNER_EMAIL` / `OWNER_PASSWORD` (and
  `OWNER_NAME`) create it; without them the site shows only that it is under
  construction. Where demo data is seeded, its first account is the owner. For now an
  owner is a superadmin like any other (`users.is_owner` keeps the door open).
- **Dev and staging run on demo data.** Every deploy to dev starts its database and
  uploaded files over with the demo data; staging starts over only by hand (the console's
  Deployments page, or the `fresh` workflow), at least once, then keeps its data across
  deploys. Production never starts over.
- **The lottery takes its time.** Solving stays as fast as it is; people see the
  lottery work, step by step (reading the preferences, weighing each type, finding the
  assignment, checking it), for at least the time superadmins set in the platform
  console (20 seconds unless set, up to 5 minutes). The assignment found is held on the
  run and written when that time is up, so every page shows the lottery running until
  then, and its result everywhere at once. It runs only while something works the queue
  (each worker leaves a mark as it loops): while nothing does, the lottery page says so to
  everyone and running it is refused, before anyone tries; Docker restarts a worker that
  stops (`restart: unless-stopped`).
- **Uploads.** Files are checked by content, never by name; SVG is refused; images are
  refused past 50 megapixels (a small file can claim more than can be opened); files
  are served to the project only, with `nosniff`, inline only what cannot run in the
  page. A person's picture is drawn anew, 512 px at most, as WebP: light wherever it
  shows, nothing of the original's metadata (where a photo was taken) kept.
- **Thesis parked.** The tutor already reviewed the published draft. It is updated at
  the very end, from the tutor's review, with notes marking each change.

## Decisions (taken autonomously, for review)

- **Refactor in place**, not a rewrite in another stack.
- **Memberships.** `memberships(user, project, family?, is_admin)`, one per user and
  project. An admin may also belong to a family of the same project (a cooperative's
  admin is often a cooperativist too); someone may also be in with neither, having joined
  from the directory, until they have a family.
  `users.is_superadmin` replaces the `SUPERADMINS` env list (granted with an artisan
  command). The Admin/Member model subclasses go.
- **Project in the URL** (`/projects/{project}/families/…`) instead of a "current
  project" cookie: links are shareable, tabs independent, notification links exact.
  Child models are resolved through the project (scoped bindings), and policies check
  membership explicitly — no global project scopes, no call-stack inspection.
- **Invitations are records** (`invitations`: project, family?, admin flag, email,
  hashed token, inviter, expiry, acceptance). Accepting one creates the account or, for
  an existing account, adds the membership. The token is no longer the password. An
  existing account also finds the invitation under its bell, in its own language,
  leading to where it joins.
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
- **Families and people, one place.** The navigation has one place for the
  cooperative's people ("People"; "Cooperativistas" in Spanish, "Coopérateurs",
  "Soci", "Cooperados"), seen by family or everyone; each view keeps its own address,
  search and details. Names are tailored to housing cooperatives now; white-labelling
  would rename them. It frees a phone tab: Home, Lottery, People, Units.
- **Google sign-in** finds the account linked to the Google account, else the one with
  its email, else opens a new one (email already confirmed); an email Google has not
  verified never opens or links an account.

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
   fits, with "Tell me more", which starts a guided tour of that spot.
7. **Polish**: containers, documentation rewritten against the code, E2E journeys
   rewritten.
8. **Parity with the pre-refactor app** (behaviour and rules compared in
   [behaviour-changes.md](behaviour-changes.md); `452ca64~1`, at stg.mtav.uy with the same demo
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
   home, in the order people look for things (its figures, recently shared files, the
   lottery and what is coming, its people, its units by type, and for admins the path
   to the lottery until done; that path comes first while the project is still empty),
   painted at once with each part a skeleton until its data arrives; the setup overview (progress, what needs
   attention, limits), the platform console (projects awaiting approval, activity).
10. **The plan, richer** (the author's ideas; units, types and the plan already share one
    page, where admins edit the plan in place). Kinds are added from a palette that shows
    each one's look, each in a starting shape of its own (a street long and narrow, an
    entrance a gate pointing in, moved, resized and rotated but never reshaped); a shape's
    side moves by dragging its edge, and the shape rotates from a handle above it (or R).
    A shape's kind is never changed: it is removed and the right one added. The plan saves
    itself a moment after each change (the state and "Done editing" in the panel's title,
    where "Edit the plan" was), and its undo history outlives a reload. Shapes have a
    context menu that holds all they need (name, rotate, colour, one floor up or down,
    bring to front, send to back, duplicate, copy, paste, remove; a corner's menu removes
    it), a double-click names a shape in a small field where it stands, Ctrl+C / Ctrl+V /
    Ctrl+D and Ctrl + drag copy them, Page Up / Page Down move them between floors. The
    selected shape's details sit beside the canvas only where they fit (full desktop);
    elsewhere the menu does it all. Units are a layer of their own on each floor, over
    every other shape: bringing to front or sending back orders a unit among units, any
    other shape among the others, and nothing ever covers a unit. The canvas fits its
    container's width; dragging its bottom edge makes it taller or shorter (never past
    what is drawn). Upper floors show the floors beneath, faintly, each as it looks (its
    shapes one image, not each one through the others), in the editor and wherever people
    read the plan. The plan's panel opens over the whole window, to draw or to look. Read,
    the plan zooms and moves as a map does: Ctrl (⌘) + scroll or a pinch zooms where the
    pointer is, dragging moves once zoomed (two fingers on a touch screen), buttons zoom
    and go back to the whole plan, and a plain scroll stays the page's, only saying how
    to zoom. Dragging a shape, a corner or a side snaps
    it to what it lines up with (other shapes, the floors beneath, the boundary, its own
    corners: a side ends up straight), with a dashed guide; a shape's name shows on hover
    and keeps clear of shapes drawn over it. Units are added from the palette too (their
    details asked first, then placed where drawn), and the units page lists them by type
    as small chips in a card that folds away. A type may carry a size on the plan: its new
    units start that size (plain squares when none is set). The editor outlines the floor
    just above, and drags line up with it too. Once the site is drawn, a unit added from the
    units page waits in the editor's rail ("To place") instead of landing on the drawing;
    a click puts it on the floor being edited. The plan tells people what is what and where,
    because they choose by it (near the playground, the parking, the common spaces; far
    from the street): besides the boundary, streets, green areas and common spaces it has
    entrances, parking and playgrounds; parks are drawn with trees (more as they grow);
    common spaces look clearly unpickable; housing units carry a subtle house mark,
    recognisable at first sight. The plan views fit what is drawn (no empty canvas
    around it). Attractive and legible first, then precise. It has depth, never a flat
    sketch: buildings and units with shadow and light (gradients, a raised edge), green
    areas filled with trees on a transparent ground rather than plain shapes, streets
    with a paving texture and markings, each kind of item recognisable by its look. The
    tutor values the plan most: it is where the app should overdeliver.
    A new shape is named as its kind is ("Green area"), a name that may be cleared (then
    nothing shows; hovering still says what it is). Playgrounds show play equipment (a
    swing, a slide, a sandpit, a roundabout), as green areas show trees. Drawing takes a
    large screen and a precise pointer; phones read the plan, full screen and zoomed.
    One admin draws at a time: the editor holds the plan while open (renewed every half
    minute, let go when done, by itself soon after a window closes or loses its
    connection, and after ten minutes alone with a warning first, two more to answer it,
    its drawing saved), and anyone else is told who is drawing; a save over someone
    else's hold is refused. On the lottery page the plan spans the page, the family's
    ranking under it as numbered places: a unit dragged off the plan (mouse or pen) onto
    a place takes it, a unit clicked on the plan lights up in the ranking, and the unit
    being moved or pointed at in the ranking lights up on the plan.
    Common spaces show a place for gatherings (a one-floor hall with its garden), one
    drawing as large as the space; units a faint hint of their rooms, along their own
    sides, never fighting their name.
    *Nice to have*: a real scale, so whoever draws from a sketch places things in metres,
    not in the canvas's units (how is not decided).
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
15. **A landing page** that invites people in (built: `pages/Welcome.tsx`): what MTAV is
    and who it is for, "Sign in" as the main call to action, signing in and up in a dialog
    over it (`/login` and `/register` open it), a language switch whose choice the account
    keeps on signing in, and signing in with Google once its client is set up (see
    technical/delivery.md). Its photos are Uruguayan housing cooperatives: families
    building by mutual aid, from the cooperatives themselves (the first version's), and
    finished cooperatives from Wikimedia Commons (CC BY-SA 4.0, credited on the page).
    More of the cooperatives' own are welcome. Its first picture moves on by itself
    (VICMAN, the first housing cooperative in Uruguay, then Mesa 1; still for whoever asks for
    less motion), the others stay still; it leads to the member manual.
16. **Illustrated manuals**, one per role (member, admin, superadmin), in every language:
    every place and action shown in a picture of the real app, cut to what matters and
    marked (where to click, numbered as the text refers to it), next to short steps. The
    pictures are taken by `scripts/guide-shots` from the demo data, per language; they
    follow considerable UI changes, not every small one. The member manual is public, at
    `/manual`: the manual alone, the device's theme, the browser's language; the admins'
    and superadmins' stay in the app.
17. **Branches and their deploys, with the author**: `main`, `staging`, `dev` (work
    happens on `dev`); the first pushes to `dev` and `staging` deployed, then `main`; the
    per-environment deploy keys set up on the host (see *Delivery from scratch*).
18. **The author's queue of 2026-10-30**:
    - The demo seed a plan worth showing: a realistic, non-rectangular site, much green,
      houses set out as they would be, buildings of one, two and three floors (some units
      on two floors, some buildings a unit per floor), playground, parking, inner streets.
    - Units drawn as a very light, much simplified house plan (rooms, kitchen, bathroom),
      recognisable as one, never fighting the name.
    - Every page a (?) by its title: the page's core in a short paragraph and its tour.
      For new people tours run as they arrive at each page, easily dismissed; every tour
      offers to show no more, and Preferences turns them back on (the (?) still has them). The tour blurs the rest of
      the page a little; a subtler outline.
    - Agreed ideas, built (see technical/data.md and notifications.md): the app installs
      on devices; production's database and files pulled every night to the author's
      machine (its address changes, so the host cannot push); people deleting their own
      account; people, families and units as spreadsheets for admins; the app keeping its
      own errors (the owner emailed of new ones); every change and every way in recorded
      for superadmins, to make everything auditable, never to watch people; a forum.
19. **The author's review of 2026-10-30 (night)**:
    - The ID number (cédula), when someone gives it, is seen by them and their projects'
      admins (legal use); the privacy page says so.
    - The (?)'s button says "Tell me more" (written natively per language): there is not
      always something to show.
    - The project's home: every panel folds (remembered per device); what was shared and
      what is coming up side by side; the lottery hidden until it has a date, then a wide
      card up front; once it has run, the road to it is gone.
    - Quick actions for admins, in this order: invite a member, add a family, set up
      units, create an event, upload pictures or files (written natively per language).
    - The lottery page shows ranking only to people in a family; its tour covers both
      roles (someone in both walks through both).
    - Dates are picked on a calendar in the person's language (weeks from Monday, 24-hour
      time), the same in every browser.
    - A project's admin pages are one "Settings" place at the bottom of the sidebar, set
      apart: Setup (its families, people and units a click away, and the checklist),
      Invitations, Project. Figure cards fit their names. An unlimited project shows
      nothing about room; only "Limited" is ever said.
    - Notifications reach people's devices (Web Push), each in its reader's language
      (notifications keep translation keys and values, written out per reader).
