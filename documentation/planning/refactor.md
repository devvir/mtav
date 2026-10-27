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
- **White-label, later (nice-to-have).** The deployment decides the vocabulary and
  shape of the grouping concept. Internally the lottery participant is the group, not
  its users. MTAV calls the group a *family*; another deployment may name it
  differently, or hide it entirely (each user is silently a group of one). Until then
  the code keeps families, but avoids assumptions that would block this.
- **Three UIs, one app.** A member app (`/projects/{p}`) for what people do in a
  project — admins in a family use it too; a project admin area (`/projects/{p}/manage`)
  for configuring a project; a platform console (`/admin`) for superadmins. They share
  the domain layer (models, services, policies) and the design system; each has its own
  routes, controllers, layout and pages, shaped for its audience.
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
- **Forms (stage 4, open with the author):** our own definitions of field constraints,
  used by validation and passed to hand-written Vue forms as hints. FormService and the
  two `devvir/*` packages go.

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
6. **Polish**: UI pass, containers and deployment, documentation rewritten against the
   code, E2E journeys rewritten.
