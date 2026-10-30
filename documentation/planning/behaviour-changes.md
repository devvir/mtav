# MTAV: what changed in behaviour, old app vs new app

- **Old app:** commit `2c8e8b13` (`452ca64~1`, 2026-07-13).
- **New app:** the `dev` working tree at `b1ec80c0`. Nothing that touches the lottery, policies, routes or services differs from what is committed.
- **Evidence:** old paths are written `2c8e8b13:path`, new paths plainly.

Every item was read in code at both commits, and nothing was run. Claims that could not be confirmed say "unverified" and what was checked. Visual and layout changes are left out.

## Lottery algorithm: unchanged?

**Verdict: the algorithm is unchanged.** Every file that decides an assignment is byte-identical between the two commits, except two lines that only move a timer used for logging. This covers:
- how the GLPK models are generated;
- the two phases: first make the worst-off family as well off as possible (max-min), then maximise overall satisfaction;
- the random tie-breaker;
- how mismatched counts are balanced (pruning the least wanted units, or adding mock units);
- how GLPK's answer is read.

The code around the solver was rewritten but behaves the same:
- the per-unit-type rounds, then one extra round for leftover families and units;
- how the result is written back.

**The default solver setting changed, with no effect on what people used.** In the old code, unless `LOTTERY_SOLVER` was set, the lottery used `RandomSolver` (a plain shuffle); the new default is `glpk`. The old deployment set `LOTTERY_SOLVER=glpk` in its own environment, and its running app answers `glpk` for `config('lottery.default')` (checked on the server, 2026-10-30). So the old app people used ran GLPK, as the new one does. Evidence: `2c8e8b13:config/lottery.php`, `config/lottery.php`.

**One change outside the algorithm can change which unit a family gets:**

1. **The order used for units a family did not rank changed.** Old: a family had to rank every unit of its type. A family that never saved a ranking got a random shuffle, stored the first time anyone read its ranking (positions 1000 and up). New: a family may rank only some units, or none. The rest follow in an order fixed per family: a SHA-256 hash of the app key, the family and the unit.
   - Both orders are arbitrary. For the same situation they are not the same, so a family relying on the fill-in may get a different unit.
   - Units a family ranked itself reach the solver exactly as before.
   - Evidence: `2c8e8b13:app/Services/Lottery/PreferencesService.php` (addMissingPreferences, missingPreferences, validateBeforeUpdate), `app/Services/Lottery/PreferencesService.php` (ranking, defaultOrder, rank).

### Files on the solver path, and their diffs

The models are not stored as files: no `.mod` or `.lp` files exist at either commit. `ModelGenerator.php` writes the GLPK (GMPL) models and `DataGenerator.php` writes their data, at run time.

| File | Old → new | Effect |
|---|---|---|
| `app/Services/Lottery/Solvers/GlpkSolver.php` (balanced / more units / more families) | identical | none |
| `app/Services/Lottery/Solvers/Glpk/Glpk.php` (chooses the strategy, falls back to binary search on timeout) | identical | none |
| `…/Glpk/ModelGenerator.php` (GMPL models for both phases and for pruning) | identical | none |
| `…/Glpk/DataGenerator.php` (preference ranks plus the `mt_rand` tie-breaker) | identical | none |
| `…/Glpk/SolutionParser.php` (reads GLPK's solution) | identical | none |
| `…/Glpk/SpecBalancer.php` (prunes the worst units, adds mock units) | identical | none |
| `…/Glpk/TaskRunners/{TaskRunner,TaskRunnerFactory,GlpkDistribution,MinSatisfaction,UnitDistribution,WorstUnitsPruning}.php` | identical | none |
| `…/Glpk/TaskRunners/HybridDistribution.php` | 2 lines moved | (a) no effect, see below |
| `…/Glpk/{lib/Files.php, lib/Process.php, Logger.php, DataObjects/*, Enums/*, Exceptions/*}` | identical | none |
| `app/Services/Lottery/Solvers/RandomSolver.php`, `TestSolver.php` | identical | none |
| `app/Services/Lottery/Contracts/SolverInterface.php`, `DataObjects/LotterySpec.php`, `DataObjects/ExecutionResult.php` | identical | none |
| `config/lottery.php` | default solver `random` → `glpk`; the GLPK settings (timeouts, phase-1 limits) are identical | none in practice: the old deployment set `glpk` itself (above) |
| `app/Services/Lottery/DataObjects/LotteryManifest.php` | it used to build its own data; now it holds data built by `LotteryService::manifest` | (a) the same layout, built the same way (see the next row) |
| Building the manifest: `2c8e8b13:…/LotteryManifest.php` buildFromProject → `app/Services/LotteryService.php` manifest | same grouping: the project's families grouped by unit type, each with its full ranking, plus the units of that type | (a) except for the ranking it reads (next row) |
| `app/Services/Lottery/PreferencesService.php` | rewritten | **(b)** see the change above |
| `app/Services/Lottery/LotteryOrchestrator.php` | rewritten | (a) see below |
| Writing the result: `2c8e8b13:…/ExecutionService.php` applyResults → `app/Services/LotteryService.php` complete | the same `units.family_id = family` for each pick; now in one transaction, limited to the project | (a) |

**`HybridDistribution.php` hunk, class (a).** It moves `$startTime = microtime(true);` from after the binary search to before it. `$startTime` feeds only `time_ms` in the task's metadata (`TaskRunner::taskResult`). Nothing in the assignment uses it.

**`LotteryOrchestrator.php` rewrite, class (a).** Same flow: one solve per unit type, then `distributeOrphans` pools every leftover unit and offers it equally to every leftover family, then one more solve. The differences:
- **Empty groups.** A group with families but no units now skips the solver and returns its families as leftovers at once. Old, the solver padded the group with mock units and every family came back as a leftover. Same outcome.
- **Joining the picks.** Old used `mapWithKeys`, new uses `+`. The keys are family ids and a family is in only one group, so the result is the same.
- **Leftovers reported.** New takes the last round's leftovers. Old's `flatMap` over the `['families','units']` arrays merged string keys, so the last round won there too. Same outcome.
- **Errors.** Old caught them in the orchestrator, recorded an audit and reopened the lottery. New lets them through to the `RunLottery` job, which records the run as *failed*. This changes how errors are handled, not results.
- **Records.** Events (`GroupLotteryExecuted`, `ProjectLotteryExecuted`) were replaced by direct audit records.

**Other (a) items.** `AuditService`, the lottery exceptions, the run records (`LotteryRun`), the jobs (`RunLottery`, `PublishLotteryRun`), the minimum time a run is shown and the queue-worker check change when and how the result appears, never what it is.
- The held result is written exactly as found (`app/Jobs/PublishLotteryRun.php`).
- Preferences cannot change while a run is in progress, before or now, so the input cannot shift between start and solve.

**The GLPK program is the same version.** Both commits install Alpine's `glpk` package (`2c8e8b13:docker/services/php/Dockerfile`, `docker/services/php/Dockerfile`, `deploy/Dockerfile`). `glpsol --version` answers "GLPK LP/MIP Solver 5.0" in the old deployment, in the new staging and in local development (checked 2026-10-30).

**Not reproducible in either version.** `DataGenerator.php` adds a random tie-breaker (`mt_rand`, at most 0.0001) to each preference rank. When several assignments are equally good, which one wins can differ between runs. The code is identical at both commits.

### Were the tests that check outcomes loosened?

**No.** In the solver tests that survived, no expectation was relaxed. Some outcome tests were deleted along with the old test suite, though.

**Byte-identical:**
- `tests/Unit/Lottery/Glpk/{DataGeneratorTest, ModelGeneratorTest, SolutionParserTest, SpecBalancerTest, FailureHandlingTest, GlpkOrchestratorTest}.php`;
- `tests/Unit/Lottery/Glpk/Runners/*` (GlpkDistribution, HybridDistribution, MinSatisfaction, UnitDistribution, WorstUnitsPruning: correct minimum satisfaction, honouring the minimum, pruning the least preferred unit);
- `tests/Unit/Lottery/{RandomSolverTest, TestSolverTest}.php`;
- the dataset `tests/Datasets/BinarySearchEquivalenceDataset.php`.

**Changed:**
- **`MoreFamiliesTest.php`, `MoreUnitsTest.php`:** only how the orchestrator is called changed (`orchestrator($solver)->execute($manifest)`). Every `expect` is unchanged.
- **`LotteryOrchestratorTest.php`:** every expectation on picks and leftovers is unchanged.
  - Assertions on dispatched events became assertions on audit records, with the same counts: two groups, one project.
  - The failure test changed from "returns empty picks and the orchestrator records the audit" to "the error is passed on", because handling moved to the job. The job is tested in `tests/UseCases/Lottery/ExecutionTest.php` ("closes a run that fails…").
- **`tests/Stress/Lottery/Glpk/GlpkTimeoutTest.php`: made stricter.** The old fixture listed family key `1` twice, so it had one family, not two. It also passed `min_satisfaction` as an argument the method ignores. The new test has two families competing for the same first choice and sets the minimum through `withContext`, so it now tests infeasibility for real.
- **`BinarySearchEquivalenceTest.php`:** only adds `fakeAudits()`.

**Deleted, and not carried over one for one.** These went with the old database-based tests in commit `452ca64f`.

`2c8e8b13:tests/Feature/Lottery/Glpk/GlpkSolverTest.php` asserted on the solver alone:
- exact picks for a 3×3 case;
- both families get their first choice in a 2×2 case (max-min);
- total satisfaction ≤ 10 in a 4×4 tie case;
- identical picks over three runs;
- a 10×10 case is complete;
- leftover units and families in mismatched cases.

Also deleted: `LotterySystemIntegrationTest`, `ExecutionServiceTest`, `PreferencesValidationTest`, `CustomAuditsTest`, `ArtifactTrackingTest`, `AsyncExecutionTest`, `ConfirmationFlowTest`, `LotteryRetryTest`, `LotteryRoutesTest`, `tests/Unit/Lottery/AuditServiceTest.php` and `LotteryExecutionFlowTest.php`.

The new end-to-end tests run through real GLPK (`tests/UseCases/Lottery/ExecutionTest.php`):
- every family gets exactly one unit of its type;
- everyone gets their first choice when choices do not collide;
- the worst-off family is protected first (the only max-min assignment of a 3×3 case).

**Brought back.** The solver checks of that file (the twelve that need no database: exact picks, max-min, tie-breaking, run-to-run stability, 10×10, leftovers, a missing glpsol, temporary files) are back word for word in `tests/Unit/Lottery/Glpk/GlpkSolverTest.php`, and all pass against the new code. Its two remaining tests drove the old lottery page and are covered by `tests/UseCases/Lottery/ExecutionTest.php`.

## Biggest changes

1. **Open sign-up, and anyone can start a project.**
   - Old: no sign-up route at all (`RegisteredUserController` existed but was never routed), and only superadmins created projects (`ProjectPolicy::create` returned `false`).
   - New: anyone signs up (email, which must be confirmed, or Google) and starts projects.
   - A new project is **Limited** until a superadmin approves it on request: 200 people, 50 families, 50 units, 150 invitations a day, 25 MB per file and 500 MB in all, at most 2 unapproved projects per person.
   - Approval cannot be taken back.
2. **Roles are per project.**
   - One account can be in many projects with a different role in each.
   - An admin can also belong to a family, and then ranks units for it.
   - Old: admin or member was global, and a member was in one project only.
3. **Invitations are records that expire and can be revoked.**
   - They last 14 days, and only a hash of the token is stored. Old: the token was the account's first password and never expired.
   - People who already have an account can be invited.
   - Invitees accept or decline.
   - Members still invite only into their own family.
4. **Lottery runs are records.**
   - A run is *running*, *completed*, *failed* or *invalidated*, is solved in the background, and is shown as running for a minimum time that superadmins set (20 s by default).
   - Running is refused while no queue worker is alive. Old: the lottery stayed stuck "executing".
   - A failure notifies the admin who started it, and the lottery can run again.
5. **Invalidation moved from superadmins to project admins.**
   - Only a completed run can be invalidated. Old: at any time, even mid-run.
   - A reason of 10–1000 characters is required.
   - Every member sees who invalidated, when and why, on the lottery page and in a notification.
6. **Preferences.**
   - Families may rank only some units. Old: they had to rank all.
   - Admins who belong to a family can rank.
   - Other families' full rankings no longer leak through the lottery page data. Old: the audit records sent to every member held them, with the admin's email.
   - The audit is now kept forever, but **no page shows it** (see Possibly missing).
7. **What admins can do to people changed.**
   - Admins can now promote, demote and remove admins (never the last one) and move people between families.
   - Removing someone ends their membership, not their account.
   - Admins can no longer edit people's names or emails.
   - Members can no longer edit their own family or its unit type.
   - The lottery freezes unit types once it has run.
8. **Deleting a project archives it, or wipes it if fresh.**
   - Old: a project with members could not be deleted at all.
   - A superadmin console (`/admin`) approves projects, restores archived ones and deletes them for good.
   - It also grants and revokes superadmin (now stored on the account, not an environment list), sets the lottery's minimum time and deploys.
   - The first superadmin (owner) is created from `OWNER_*` settings.
9. **Files are private to the project.**
   - Old: public URLs anyone could open, and "deleted" files stayed reachable.
   - Types are checked by content. SVG and AVI are refused, and images over 50 megapixels too.
   - At most 20 files per share.
   - Limited projects: 25 MB per file and 500 MB in all. Unlimited projects go from 100 MB to 200 MB per file.
10. **Notifications.**
    - Old: every change to anything went to the whole project, including draft events' titles, and nothing was ever pushed live.
    - New: a few meaningful ones (lottery completed, failed or invalidated; member joined; invitation received; approval), pushed in real time on channels that check access.
11. **RSVP works.** Old: it existed in the database and in the thesis, but nobody could turn it on or answer. New: admins ask for attendance, each person answers, and attendees are listed.
12. **Privacy, languages, reach.**
    - Email and phone are private unless the person shares them (admins always see them).
    - Admins no longer see the ID number (cédula).
    - There is a public landing page and a public member manual.
    - Five languages, chosen per person. Old: one per deployment.
    - Pages can be read offline, and changes are refused while offline.

## Possibly missing from the new app

- **MISSING? Seeing or verifying the lottery audit.** The new app stores every run's audit, including its input and steps, but no page, route or resource shows it to anyone, superadmins included. The thesis says anyone can verify the result (`documentation/thesis/parte-3-el-sorteo.md` §17). Old: the audit was sent to the lottery page only as raw page data. (`app/Models/LotteryRun.php`, `2c8e8b13:app/Http/Controllers/LotteryController.php` `with('audits')`)
- **MISSING? Deleting one's own account.** Old: members could, and admins could unless they were a project's only admin. Agreed in refactor.md item 18, not built. (`2c8e8b13:app/Policies/MemberPolicy.php`, `2c8e8b13:app/Policies/AdminPolicy.php`)
- **MISSING? Admins correcting a person's name or email.** (`2c8e8b13:app/Http/Requests/UpdateMemberRequest.php`)
- **MISSING? Superadmins deleting, restoring or blocking accounts.** The console's Accounts page is read-only. (`2c8e8b13:app/Http/Controllers/Resources/AdminController.php` restore; `app/Http/Controllers/Platform/UserController.php`)
- **MISSING? Notifications for new events, files, families and units.** The thesis cites "an event is published" as a live notification (`parte-2` §6.6). New only refreshes pages that are open.
- **MISSING? Choosing the first admin when a superadmin creates a project.** A superadmin's new project has no admin until someone is invited. (`2c8e8b13:app/Http/Requests/CreateProjectRequest.php`, `app/Services/ProjectService.php`)
- **MISSING? Admins seeing a person's ID number (cédula)**, and whether they confirmed their email or accepted their invitation. (`2c8e8b13:app/Http/Resources/UserResource.php`)
- **MISSING? One invitation making someone admin of several projects.** (`2c8e8b13:app/Http/Requests/CreateAdminRequest.php`)
- **MISSING? Search and the "active / all" filter on the projects list.** The `active` column remains but nothing reads it. The old app could not switch it either. Refactor.md item 8 already notes this.
- **MISSING? Lists that span several projects** (families, members and admins of all one's projects, or the whole platform for superadmins). Probably dropped on purpose with the project in the URL.
- **MISSING? Restoring deleted files or events.** Old soft-deleted them, but had no restore route.
- **MISSING? Global notifications for admins of several projects** (project created, deleted, restored). New global notifications go to superadmins only.
- **MISSING? The activity log page (`/logs`).** Old: open to every signed-in user, but only demo data ever filled it. Refactor.md item 18 plans auditing for superadmins.
- **MISSING? Recovering from an expired page (419).** Old: the app refreshed its security token and retried. Unverified in a browser.
- **MISSING? Asking for phone, ID number and picture when an invitation is accepted.** Now they are set later in the profile, probably on purpose.
- **Never existed in either version:**
  - emailing lottery results to families;
  - taking back a project's approval (approval itself is new);
  - legal pages;
  - the assistant (planned in refactor.md item 14).

---

# Changes by area

Each area lists, in order: what is no longer possible, what is newly possible, and what works differently.

## Accounts & sign-up

### No longer possible / newly restricted

- **Deleting one's own account.** Old: a member could delete their own account, and an admin theirs unless they were a project's only admin (both soft deletes, offered through the card's Delete action when the policy allowed it). New: no route deletes an account. A person can only leave a project, and the last admin of a project cannot leave it. (`2c8e8b13:app/Policies/MemberPolicy.php` delete, `2c8e8b13:app/Policies/AdminPolicy.php` delete, `2c8e8b13:app/Http/Controllers/Resources/AdminController.php` destroy, `2c8e8b13:resources/js/components/card/CardActions.vue`; new `routes/web.php` has no account delete; `app/Http/Controllers/Member/PeopleController.php` leave)
- **Admins editing a person's name or email.** Old: an admin could change any member's first name, last name and email, and the new email took effect at once with no confirmation. A member could also change their own email this way, skipping confirmation. New: admins change only a person's family and admin role. Names and email are changed only by the person, in their profile, and a new email needs confirming. (`2c8e8b13:app/Http/Requests/UpdateMemberRequest.php`, `2c8e8b13:app/Policies/MemberPolicy.php` update; `app/Http/Controllers/Manage/PeopleController.php` update, `app/Http/Controllers/Settings/ProfileController.php`)
- **Everyone's email and phone visible to everyone.** Old: every person's email and phone were sent to every viewer and shown on member and admin cards. New: email and phone are hidden by default. The project's admins and the person themselves always see them; anyone else sees them only if the person chose to share them. Searching by email follows the same rule. (`2c8e8b13:app/Http/Resources/UserResource.php`, `2c8e8b13:resources/js/components/entities/member/ShowCard.vue`, `2c8e8b13:resources/js/components/entities/admin/ShowCard.vue`; `app/Http/Resources/MemberResource.php`, `app/Models/User.php` scopeSearch, `database/migrations/2026_10_28_000001_add_contact_sharing_to_users.php`)
- **Avatar uploads are stricter.** Old: any image up to 2 MB, stored as uploaded. New: JPEG, PNG or WebP only, up to 8 MB and 50 megapixels. The picture is redrawn at 512 px at most as WebP, which strips its metadata, and the previous picture is deleted. (`2c8e8b13:app/Http/Requests/Settings/AvatarUpdateRequest.php`, `2c8e8b13:app/Http/Controllers/Settings/AvatarController.php`; `app/Http/Controllers/Settings/AvatarController.php`, `tests/UseCases/Accounts/AvatarTest.php`)
- **Unconfirmed accounts are kept out.** Old: the `verified` middleware did nothing, because `User` did not implement `MustVerifyEmail`. In practice invitees were marked verified when they accepted. New: an account must confirm its email before it reaches projects, settings, notifications or project creation. Until then, `/` sends it to the "Confirm your email" page, which offers only resending the link and signing out. (`2c8e8b13:app/Models/User.php`; `app/Models/User.php` implements MustVerifyEmail, `routes/web.php` verified group, `app/Http/Controllers/HomeController.php`, `app/Policies/ProjectPolicy.php` create, `tests/UseCases/Accounts/WelcomeTest.php`)
- **Confirming an email change needs the right account.** Old: the confirmation link found the user by the id in the URL, so any signed-in session could complete it. New: the link works only when signed in as that account. It also refuses with 409 if the new address was taken in the meantime. (`2c8e8b13:app/Http/Requests/Auth/VerifyPendingEmailRequest.php`; `app/Http/Controllers/Auth/EmailVerificationController.php` verify)
- **Login is also rate-limited per IP.** Old: only the 5-failure lockout per email and IP. New: the same lockout, plus 10 login requests per minute and 5 sign-ups per 10 minutes on the routes. (`2c8e8b13:routes/web/guest.php`; `routes/web.php`, `tests/UseCases/Accounts/SignInTest.php`)

### Newly possible / newly permitted

- **Open sign-up.** Old: joining was by invitation only. `RegisteredUserController` existed but had no route, and the thesis says "no existe registro público". New: anyone can sign up at `/register` (a dialog over the landing page) with first name, last name, email and password, then confirms the email. (`2c8e8b13:routes/web/guest.php`, `2c8e8b13:app/Http/Controllers/Auth/RegisteredUserController.php`, `documentation/thesis/parte-2-descripcion-funcional.md` §6.2; `routes/web.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`)
- **Google sign-in.** Offered only when the deployment sets up a Google client. It signs in the account linked to that Google account, or else the account with that email, or else opens a new account with the email already confirmed. If Google has not verified the email, it never opens or links an account. If an account with that email was never confirmed, Google sign-in takes it over: it sets a new random password and ends that account's other sessions. Someone who only signs in with Google has no known password and must use "forgot password" to get one, because changing the password asks for the current one. (`app/Http/Controllers/Auth/GoogleController.php`, `tests/UseCases/Accounts/GoogleSignInTest.php`, `database/migrations/2026_10_29_000002_add_google_id_to_users.php`, `app/Http/Controllers/Settings/PasswordController.php`)
- **Sharing contact details is a choice.** Two profile switches, "Show my email/phone to the people in my projects", both off by default. (`app/Http/Controllers/Settings/ProfileController.php`, `resources/js/pages/Settings/Profile.tsx`)
- **The old address is told about an email change.** Besides the confirmation link sent to the new address, the current address now gets a notice of the change. (`app/Notifications/EmailChangeNotice.php`, `tests/UseCases/Accounts/EmailChangeTest.php`)
- **Resending the confirmation email.** Up to 3 times per 10 minutes. (`routes/web.php` verification.send)
- **A language per account.** The profile has a language preference, used for the app and for that person's mail. (See Languages; `app/Http/Controllers/Settings/ProfileController.php`)
- **Belonging to several projects.** One account can be in any number of projects, with a different role in each. Old: a member belonged to one project, and an account was either an admin or a member everywhere. (`2c8e8b13:app/Models/User.php` is_admin / asMember / asAdmin; `app/Models/User.php` memberships, `documentation/planning/refactor.md` "Per-project roles")

### Works differently

- **Someone with no project.** Old: a non-superadmin with no project was signed out and sent back to the login page. New: they land on "Your projects", which shows pending invitations to accept or decline and "Start a project" (if their email is confirmed and they have fewer than 2 unapproved projects). (`2c8e8b13:app/Http/Middleware/HandleSelectedProject.php`, `2c8e8b13:tests/Feature/Authentication/ForcedLogoutTest.php`; `app/Http/Controllers/ProjectController.php` index, `app/Policies/ProjectPolicy.php` create, `config/limits.php`)
- **Where signing in leads.** Old: to the current project's dashboard, or the projects list for an admin of several. New: superadmins go to the platform console, people in exactly one project go to that project, and everyone else goes to "Your projects". (`2c8e8b13:app/Http/Controllers/Auth/AuthenticatedSessionController.php`; `app/Http/Controllers/HomeController.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`)
- **Email confirmation covers sign-up too.** Old: the confirmation link was used only for changing email (`new_email`). New: it confirms the sign-up address as well, and accepting an invitation sent to one's own address also counts as confirmation. (`2c8e8b13:app/Http/Controllers/Auth/VerifyEmailController.php`; `app/Http/Controllers/Auth/EmailVerificationController.php`, `app/Services/InvitationService.php` accept)
- **Password rules and reset are unchanged.** Both use `Password::defaults()` (Laravel's default, minimum 8 characters), the reset link is limited to 3 requests per 5 minutes, and changing the password asks for the current one. The password page is now a tab of the profile page instead of `/settings/password`. (`2c8e8b13:routes/web/settings.php`; `routes/web.php`, `app/Http/Controllers/Settings/PasswordController.php`)
- **The profile no longer collects the ID number at invitation.** Old: completing an invitation asked for a password plus first name, last name, phone, ID number and avatar. New: an invitee gives only first name, last name and password. Phone, ID number and picture are set later in the profile. The ID number is still shown only to admins. (`2c8e8b13:app/Http/Requests/CompleteRegistrationRequest.php`; `app/Http/Controllers/InvitationController.php` createAccount, `resources/js/pages/Settings/Profile.tsx` "Only admins see it")

## Projects

### No longer possible / newly restricted

- **Deleting a project now archives it or wipes it; it is never blocked.** Before, nobody could delete a project that still had active members (`Gate::denyIf`, which superadmins could not bypass either), and a project without members was soft-deleted and then gone, with no restore route or page. Now an admin deletes any project they manage by typing its name. A project with a history is archived (soft-deleted, `archived_by` set). A project its creator only set up is deleted for good, with its files and notifications. (`2c8e8b13:app/Http/Controllers/Resources/ProjectController.php` destroy; `app/Http/Controllers/Manage/SettingsController.php` destroy; `app/Services/ProjectService.php` remove/purge; `app/Models/Project.php` hasHistory; `tests/UseCases/Platform/ArchiveTest.php`)
- **An archived project is gone for its people.** Its pages answer 404, it no longer appears on anyone's projects page, and its pending invitations stop working. Only superadmins see it, in the console. (`app/Http/Middleware/ResolveProject.php`; `app/Models/User.php` reachableProjects; `app/Models/Invitation.php` scopePending `whereHas('project')`; `tests/UseCases/Platform/ArchiveTest.php`)
- **Restoring and deleting for good belong to superadmins.** Before, `ProjectPolicy::restore` let any admin restore, but no route led to it. Now restoring an archived project and deleting any project for good (after typing its name) are console actions, superadmins only. (`2c8e8b13:app/Policies/ProjectPolicy.php`; `routes/web.php` `admin.projects.restore` / `admin.projects.destroy`; `app/Http/Controllers/Platform/ProjectController.php`)
- **Starting projects is capped while none is approved.** A non-superadmin can have at most `limits.unapproved_projects_per_user` (default 2) unapproved projects, and needs a verified email to start one. Archived projects do not count, because the query excludes soft-deleted rows. (`app/Policies/ProjectPolicy.php` create; `config/limits.php`; `tests/UseCases/ProjectSetup/ProjectsTest.php` "starts only a few projects…", "needs a verified email…")
- **Unapproved (Limited) projects work within bounds that did not exist before.** The old app had no per-project limits: only a 100 MB per-file cap on uploads (`2c8e8b13:app/Http/Requests/StoreMediaRequest.php`). Each limit in `config/limits.php` is enforced here:
  - `members` (200): refuses a new invitation once memberships plus pending invitations reach the cap. It is checked only when inviting, not when someone accepts. (`app/Services/InvitationService.php` ensureWithinLimits)
  - `invitations_per_day` (150): refuses invitations once the project created that many in the last 24 hours. It sits on top of route throttles of 60 per minute (admins) and 30 per minute (members). (`app/Services/InvitationService.php`; `routes/web.php`)
  - `families` (50): refuses creating a family at the cap (`app/Http/Requests/FamilyRequest.php` after). The lottery also refuses to run with more families than the cap, as `too_large` (`app/Services/LotteryService.php` ensureCanRun).
  - `units` (50): refuses creating a unit at the cap (`app/Http/Requests/UnitRequest.php` after). Unit types and plan shapes have no project limit; the plan takes at most 2000 shapes (`app/Http/Requests/PlanRequest.php`).
  - `media_file_mb` (25) and `media_storage_mb` (500): per-file size and total storage (`app/Services/MediaService.php` maxFileKb, ensureStorageAvailable). Approved projects get `media_file_max_mb` (200) per file and no storage cap.
  - `invitation_days` (14): how long an invitation link stays valid; applies to every project (`app/Services/InvitationService.php`).
  - Approval lifts every limit (`app/Models/Project.php` limit). No route takes approval away again (`routes/web.php`: only `admin.projects.approve`).
- **People outside a project now get a 404, not a 403.** Anyone who does not take part in a project gets "not found" for every page under it (`app/Http/Middleware/ResolveProject.php`; `tests/UseCases/Membership/ProjectIsolationTest.php`).

### Newly possible / newly permitted

- **Anyone signed up can start a project.** Before, only superadmins could: `ProjectPolicy::create` returned false and the routes sat behind `MustBeSuperAdmin`. Now any verified account can, within the cap above. A non-superadmin creator becomes the project's first admin. A superadmin's project starts approved, and the superadmin gets no membership in it. (`2c8e8b13:routes/web/users.php`; `2c8e8b13:app/Policies/ProjectPolicy.php`; `app/Policies/ProjectPolicy.php` create; `app/Services/ProjectService.php` create; `tests/UseCases/ProjectSetup/ProjectsTest.php`)
- **Admins ask for approval and superadmins grant it.** An admin of an unapproved project asks for "unlimited", at most 3 times an hour, and every superadmin is notified. A superadmin approves it in the console and the project's people are notified. Nobody can approve their own project unless they are a superadmin. (`routes/web.php` `manage.approval.request`, `admin.projects.approve`; `app/Services/ProjectService.php` requestApproval/approve; `app/Policies/ProjectPolicy.php` requestApproval/approve; `tests/UseCases/ProjectSetup/ProjectsTest.php`)
- **Superadmins hear about every new project.** A global notification points to the console. (`app/Services/ProjectService.php` create)
- **Everyone in a project gets the project home.** Before, the project page (`Projects/Show`) was only for admins (`2c8e8b13:app/Policies/ProjectPolicy.php` view). Now members and admins alike see the description, organization, counts, lottery status with their family's assigned unit, events, people, files and units by type. Admins also see the setup checklist. (`app/Http/Controllers/Member/DashboardController.php`; `routes/web.php` `projects.show`)
- **Admins get a setup overview.** It shows counts (unit types, units, families, people, pending invitations, files) and a checklist: unit types, units, families, members, "balanced" (each type has as many units as families), lottery scheduled, lottery done. For a limited project it also shows usage against each limit. (`app/Http/Controllers/Manage/OverviewController.php`; `app/Services/ProjectService.php` setupChecklist)
- **Superadmins see the whole platform in a console.** It lists every project with awaiting, unapproved, approved and archived filters, plus a search. For each project it shows people, families, units, events, files, storage used, lottery runs and who created it: the summary of what a permanent delete removes. (`app/Http/Controllers/Platform/ProjectController.php` index)
- **Anyone may leave a project, unless they are its last admin.** (`routes/web.php` `membership.leave`; `app/Http/Controllers/Member/PeopleController.php` leave)

### Works differently

- **The project is in the URL, not in a cookie.** Before, the current project was a cookie (`projectId`), set or unset through `projects/current/{project}`. Project pages required a selection. A user in exactly one project had it auto-selected. A non-superadmin with no project was logged out by force. Now every project page lives under `/projects/{project}/…`; signing in leads straight to the only project, or to the projects page, or to the console for superadmins. A user with no projects lands on "your projects" (invitations, create) instead of being logged out. Different tabs can show different projects, and links name their project. (`2c8e8b13:app/helpers.php` selectProject/currentProject; `2c8e8b13:app/Http/Middleware/HandleSelectedProject.php`; `2c8e8b13:app/Http/Middleware/Auth/ProjectMustBeSelected.php`; `routes/web.php`; `app/Http/Controllers/HomeController.php`; `app/Http/Middleware/ResolveProject.php`)
- **Lists never span several projects any more.** Before, the families, members, admins and logs routes did not require a selected project. With none selected, an admin saw those lists across all their projects at once, and a superadmin across the platform; `project_id` filtered only when it was present or injected. Now every list belongs to one project. The URL does not change who may do what; it removes this cross-project view. (`2c8e8b13:routes/web/users.php`; `2c8e8b13:app/Http/Requests/ProjectScopedRequest.php`; `2c8e8b13:app/Http/Controllers/Resources/FamilyController.php` index)
- **"Your projects" is open to everyone.** Before, the projects list was paged, with a search and an "active / all" filter, and only for superadmins and admins of two or more projects. Now it lists the user's projects (every project, for superadmins) with family and member counts and pending invitations; it has no search or filter. (`2c8e8b13:app/Http/Controllers/Resources/ProjectController.php` index; `app/Http/Controllers/ProjectController.php` index)
- **Active / inactive no longer does anything.** Before, the `active` column hid inactive projects from the list unless "all" was ticked, and inactive cards were dimmed. Nothing in the app could toggle it; only seeding set it (`2c8e8b13:database/factories/ProjectFactory.php`). Now the column and `scopeActive` remain, but nothing reads or sets them; archiving takes their place. (`app/Models/Project.php`; `app/Http/Resources/ProjectResource.php`; `database/migrations/2026_10_27_000001_create_projects_tables.php`)
- **A project's admins come from invitations, not from the creation form.** Before, a superadmin picked existing admins and/or invited one new admin while creating the project. Now the creation form has name, description and organization only; the creator becomes admin, and others are invited afterwards. A superadmin's new project therefore has no admin until someone is invited. (`2c8e8b13:app/Http/Requests/CreateProjectRequest.php`; `2c8e8b13:app/Http/Controllers/Resources/ProjectController.php` store; `app/Http/Requests/ProjectRequest.php`; `app/Services/ProjectService.php` create)
- **Editing a project.** Both versions let a project's admins (and superadmins) edit name, description and organization. Changes:
  - Organization was optional when creating but *required* when updating (2–255 characters); now it is optional both times (2–120), with FUCVAM and FECOVI suggested.
  - Name now allows 2–120 characters (was 2–255) and description 2–2000 (was 2–500).
  - Settings moved to `/projects/{p}/manage/settings`.

  (`2c8e8b13:app/Http/Requests/UpdateProjectRequest.php`; `2c8e8b13:app/Http/Requests/CreateProjectRequest.php`; `app/Http/Requests/ProjectRequest.php`; `tests/Browser/Features/ProjectsTest.php` "suggests the federations…")
- **Creating a project still creates its lottery event and an empty 800×600 plan.** Before, `ProjectObserver` did this; now `ProjectService::create` does it inside the creation transaction. (`2c8e8b13:app/Observers/ProjectObserver.php`; `app/Services/ProjectService.php`)
- **A user can take part in several projects with a different role in each.** Before, a member could be in at most one project (`Member::joinProject` deactivated the others), and an admin was an admin in every project they were attached to. Now there is one membership per user and project, with its own family and admin flag. (`2c8e8b13:app/Models/Member.php`; `app/Models/Membership.php`; `database/migrations/2026_10_27_000001_create_projects_tables.php` unique(user_id, project_id))

## Families & members

### No longer possible / newly restricted

- **Members can no longer edit their own family.** Before, a member of a family could change its name and **unit type**: `FamilyPolicy::update` accepted `$user->family_id === $family->id`, and `UpdateFamilyRequest` took `unit_type_id`. Now only admins can, through the manage routes. (`2c8e8b13:app/Policies/FamilyPolicy.php`; `2c8e8b13:app/Http/Requests/UpdateFamilyRequest.php`; `routes/web.php` manage group `can:update,project`; `tests/UseCases/ProjectSetup/FamiliesTest.php` "cannot change their family's unit type")
- **Admins can no longer edit someone else's name or email.** Before, admins edited a member's first name, last name and email directly, with no verification of the new address (`UpdateMemberRequest`, `MemberPolicy::update` isAdmin). Now an admin changes only a person's family and admin role; people edit their own details in their profile. (`2c8e8b13:app/Http/Controllers/Resources/MemberController.php` update; `app/Http/Controllers/Manage/PeopleController.php` update)
- **A family's unit type is frozen once the lottery runs.** Changing it is refused while a lottery run is running or completed (`lottery.locked`). Before, nothing locked it. (`app/Http/Controllers/Manage/FamilyController.php` update; `app/Models/Project.php` lotteryLocked)
- **A family with an assigned unit cannot be deleted.** Neither can any family while a lottery run is in progress, and families cannot be created during a run either. Before, a family could be deleted at any time, even with a unit. (`app/Http/Controllers/Manage/FamilyController.php` store/destroy; `2c8e8b13:app/Http/Controllers/Resources/FamilyController.php` destroy)
- **Members see email and phone only where a person shares them.** Before, every user's email and phone went to anyone who could see them, fellow members included. Now each person decides; admins and the person themselves still see both. Searching by email also respects this: members find people by email only when the address is shared, while admins search every address. (`2c8e8b13:app/Http/Resources/UserResource.php`; `app/Http/Resources/MemberResource.php`; `app/Models/User.php` scopeSearch; `tests/UseCases/Membership/ProjectIsolationTest.php`)
- **Admins no longer see a person's legal ID (cédula).** Before, they received it with each person (`sensitiveData`). Now only the person sees it, in their profile. Also gone from what admins see: verification and invitation-accepted dates. (`2c8e8b13:app/Http/Resources/UserResource.php` sensitiveData; `app/Http/Resources/MemberResource.php`; `app/Http/Controllers/Settings/ProfileController.php`)
- **Nobody can delete their own account.** Before, `MemberPolicy::delete` allowed a member to delete themselves (a soft delete of the account), and `AdminPolicy::delete` did the same for an admin. Now there is no account deletion; people leave projects instead. (`2c8e8b13:app/Policies/MemberPolicy.php`; `2c8e8b13:app/Policies/AdminPolicy.php`; `app/Http/Controllers/Settings/ProfileController.php` has no destroy; refactor.md item 18 lists it as agreed but not built)

### Newly possible / newly permitted

- **An admin can also belong to a family in the same project.** Before, `users.is_admin` was global, and `Member`'s global scope required `is_admin = false`, so admins never had families. Now a membership has an optional `family_id` and `is_admin`. An admin in a family ranks units for it, and the lottery treats that family like any other. (`2c8e8b13:app/Models/Member.php` booted scope; `app/Models/Membership.php`; `app/Policies/ProjectPolicy.php` rankUnits; `2c8e8b13:app/Http/Requests/UpdateLotteryPreferencesRequest.php` authorize isMember)
- **One person can be in several projects.** Before, invitations required an email unique among users, so an existing account could never be invited. Now inviting an existing account adds a membership. If they are already in, an invitation can still add what they lack: a family, or the admin role. (`2c8e8b13:app/Http/Requests/CreateMemberRequest.php` unique:users; `app/Services/InvitationService.php` ensureNotAlreadyIn/accept; `tests/UseCases/Membership/InvitationsTest.php`)
- **Admins can move people between families.** Before, there was no field for it (`UpdateMemberRequest` had no family). Now an invitation never moves someone out of their family; moving people is a deliberate admin action. (`app/Http/Controllers/Manage/PeopleController.php` update; `app/Services/InvitationService.php` accept; `tests/Browser/Features/PeopleTest.php`)
- **Anyone can leave a project without losing their account.** (`app/Http/Controllers/Member/PeopleController.php` leave; `tests/Browser/Features/PeopleTest.php` "lets a member leave the project")
- **Search and filters cover email and roles.** People can be searched by email (within the sharing rule above) and filtered by family and by role; families can be filtered by unit type. Before, members were searched by name and family name, and families by name and member names. (`app/Http/Controllers/Member/PeopleController.php`; `app/Http/Controllers/Member/FamilyController.php`; `2c8e8b13:app/Models/Member.php` scopeSearch; `2c8e8b13:app/Models/Family.php` scopeSearch)
- **Families without members show in the list.** Before, the families list hid them (`withMembers()`). (`2c8e8b13:app/Http/Controllers/Resources/FamilyController.php` index; `tests/UseCases/ProjectSetup/FamiliesTest.php` "sees a newly registered family… before it has members")

### Works differently

- **Removing a member removes a membership, not an account.** Before, an admin "deleting" a member soft-deleted the user: they could no longer sign in at all, and their email stayed taken. Now only the membership in that project goes; the account, other projects and the person's files stay. (`2c8e8b13:app/Http/Controllers/Resources/MemberController.php` destroy; `app/Http/Controllers/Manage/PeopleController.php` destroy; `database/migrations/2026_10_27_000003_create_community_tables.php` media `project_id` / `owner_id` nullOnDelete)
- **Deleting a family keeps its members' accounts.** Before, the family and all its members' **accounts** were soft-deleted; preferences and the unit's `family_id` pointed at the soft-deleted family. Now the family is deleted for good. Its non-admin memberships go (the accounts stay), admin memberships stay with no family, its preferences and pending invitations into it are deleted by cascade, and an assigned unit blocks the delete. (`2c8e8b13:app/Http/Controllers/Resources/FamilyController.php` destroy; `app/Http/Controllers/Manage/FamilyController.php` destroy; `database/migrations/2026_10_27_000004_create_lottery_tables.php` unit_preferences cascade; `database/migrations/2026_10_27_000001_create_projects_tables.php` invitations.family_id cascade, memberships.family_id nullOnDelete)
- **Invitations a removed member sent stay valid.** No code withdraws them when a membership ends; they remain pending until an admin revokes them or they expire. Verified that no model hook or controller touches them; not exercised in a test. (`app/Http/Controllers/Manage/PeopleController.php`; `app/Models/Invitation.php`)
- **Every non-admin member must have a family.** This held before (Member scope `whereNotNull('family_id')`; `CreateMemberRequest` family required). Now it is enforced at three points: an invitation needs a family unless it is for an admin, demoting an admin with no family is refused, and deleting a family removes its non-admin members from the project. (`app/Http/Requests/InvitationRequest.php`; `app/Http/Controllers/Manage/PeopleController.php`; `app/Http/Controllers/Manage/FamilyController.php`)
- **Every family has a unit type, and only admins set it.** `unit_type_id` was required before too. Admins and the family's own members could set it then; only admins can now, and not after the lottery runs. It must belong to the same project in both versions. (`2c8e8b13:app/Http/Requests/CreateFamilyRequest.php`; `app/Http/Requests/FamilyRequest.php`)
- **Family names.** The name is unique per project in both versions. Before, only the database enforced it (a server error); now validation does (a message). The length is 2–120 characters (was 2–255). (`2c8e8b13:database/migrations/2025_07_10_131456_create_families_table.php`; `app/Http/Requests/FamilyRequest.php`)
- **Members invite people into their own family only, as before.** Now these invitations count against the people limit and the daily invitation limit, and a member cannot invite admins. The member who sent an invitation, or an admin, can revoke it. (`2c8e8b13:app/Http/Requests/CreateMemberRequest.php`; `app/Http/Controllers/Member/InvitationController.php`; `app/Policies/ProjectPolicy.php` invite; `app/Policies/InvitationPolicy.php`)
- **Family marks (avatars) are unchanged.** Neither version lets anyone upload a family picture; a generated mark stands in. Before, it came from an external identicon service; now it is drawn in the browser. (`2c8e8b13:app/Http/Resources/FamilyResource.php` resolveAvatar; `resources/js/design/components/FamilyMark.tsx`)

## Families & members: admin roles

### No longer possible / newly restricted

- **One invitation no longer covers several projects.** Before, `CreateAdminRequest` took `project_ids[]` covering any of the projects the inviting admin managed. Now an admin invites admins into the current project only. (`2c8e8b13:app/Http/Requests/CreateAdminRequest.php`; `app/Http/Requests/InvitationRequest.php`; `app/Policies/ProjectPolicy.php` invite)
- **Admins can no longer edit their own name and email as "an admin".** Before, the admin edit page (`AdminPolicy::update` self) did this, and email changed without verification. Now that goes through the profile, the same for everyone. (`2c8e8b13:app/Http/Controllers/Resources/AdminController.php` update)

### Newly possible / newly permitted

- **Any admin of a project can grant or revoke the admin role.** Before, admins could only invite brand-new accounts as admins and could not remove other admins (only superadmins could, through policy bypass). Now any admin can promote a member, demote or remove another admin, or demote themselves, as long as one admin remains. (`2c8e8b13:app/Policies/AdminPolicy.php` update/delete self only; `app/Http/Controllers/Manage/PeopleController.php` update/destroy; `tests/Browser/Features/PeopleTest.php` "changes someone's family and role, keeping at least one admin")
- **An existing account can be made admin of another project.** Invite its email as admin. Before, only a superadmin could attach existing admins, and only while creating a project. (`app/Services/InvitationService.php`; `2c8e8b13:app/Http/Requests/CreateProjectRequest.php` admins.*)
- **An admin can step out of one project only.** They leave it, or remove themselves, while keeping their account and other projects. Before, removing yourself as an admin meant soft-deleting your account. (`app/Http/Controllers/Member/PeopleController.php` leave; `2c8e8b13:app/Http/Controllers/Resources/AdminController.php` destroy)

### Works differently

- **Last-admin protection now covers every way out.** Before, deleting an admin account was refused if any of their projects would be left with no admin. Now leaving, being removed and being demoted are each refused for a project's only admin (`validation.last_admin`). A superadmin's new project starts with no admin at all, and nothing requires one. (`2c8e8b13:app/Http/Controllers/Resources/AdminController.php` destroy; `app/Http/Controllers/Manage/PeopleController.php` ensureNotLastAdmin; `app/Http/Controllers/Member/PeopleController.php` leave; `app/Services/ProjectService.php` create)
- **Being an admin is per project.** Before, `users.is_admin` made someone an admin of every project they were attached to, so the same account could never be a plain member anywhere. Now `memberships.is_admin` applies per project. (`2c8e8b13:app/Models/User.php` isAdmin; `app/Models/User.php` manages)
- **Superadmins can do everything inside projects, with three exceptions.** Before, they bypassed every policy (`Gate::before`) but could not rank preferences, because `UpdateLotteryPreferencesRequest::authorize` needed `isMember()`. Now they reach every project as a manager without a membership. Ranking units, answering RSVPs and deploying are excluded from the bypass; ranking needs a family in the project anyway. Superadmin status is a user flag now, no longer an env list plus `is_admin`. (`2c8e8b13:app/Providers/AppServiceProvider.php`; `2c8e8b13:app/Models/User.php` isSuperadmin; `app/Providers/AppServiceProvider.php` PERSONAL_ABILITIES; `app/Models/User.php` canAccess/manages; `tests/UseCases/ProjectSetup/ProjectsTest.php` "finds every project among their own…")
- **Admins of several projects switch between them.** Before, admins of several projects picked one through the projects list and the cookie, and could also see all of them at once. Now each project is its own address; the projects page and the switcher move between them. (`2c8e8b13:app/Policies/ProjectPolicy.php` viewAny; `tests/Browser/Features/ProjectsTest.php` "switches between projects")

## Invitations

### No longer possible / newly restricted

- **Invitation links expire.** Old: the link (email + token) never expired. New: it is valid for 14 days by default (`INVITATION_DAYS`). An expired link, or one into a project that has been archived, shows "invalid invitation". (`2c8e8b13:app/Services/InvitationService.php`; `config/limits.php` invitation_days, `app/Models/Invitation.php` isPending, `tests/UseCases/Membership/InvitationsTest.php` "cannot use an expired link")
- **The token is no longer the password.** Old: the invitation token was the account's initial password, and opening the link signed the person in with it. New: only a hash of the token is stored, and the link alone never signs anyone in. (`2c8e8b13:app/Http/Requests/EditInvitationRequest.php`, `2c8e8b13:app/Services/InvitationService.php` createToken; `app/Models/Invitation.php` hashToken)
- **Only the invited address can accept.** New: someone signed in under another email is shown the mismatch and cannot accept (403). Someone who already has an account must sign in as that address first. (`app/Http/Controllers/InvitationController.php` show/accept, `tests/UseCases/Membership/InvitationsTest.php` "cannot accept an invitation meant for someone else")
- **Limited (unapproved) projects have invitation limits.** At most 150 invitations per day, and at most 200 people counting pending invitations. Approved projects have no limit. Requests are also rate-limited: 30 per minute from the member page and 60 per minute from the admin page. (`config/limits.php`, `app/Services/InvitationService.php` ensureWithinLimits, `routes/web.php`, `tests/UseCases/Membership/InvitationsTest.php` "An unapproved project")
- **Inviting an admin into several projects at once is gone.** Old: one admin invitation could cover several of the inviter's projects (`project_ids[]`). New: an invitation is for one project. (`2c8e8b13:app/Http/Requests/CreateAdminRequest.php`; `app/Http/Requests/InvitationRequest.php`)
- **A member invites only from their own family's page.** As before, members cannot invite admins or into another family. An admin with no family cannot use the member page (404) and invites from the admin area instead. (`2c8e8b13:app/Http/Requests/CreateMemberRequest.php`; `app/Http/Controllers/Member/InvitationController.php`, `app/Policies/ProjectPolicy.php` invite, `tests/UseCases/Membership/InvitationsTest.php` "A member", "never invites from the member app someone into no family")
- **Invitees are no longer shut out of the rest of the app.** Old: an invited account that had not completed registration was redirected to the invitation from any page, and visiting the login page signed it out. New: there are no half-registered accounts, so this middleware is gone. (`2c8e8b13:app/Http/Middleware/HandleInvitedUsers.php`; `bootstrap/app.php`)

### Newly possible / newly permitted

- **Inviting people who already have an account.** Old: the email had to be unused across the whole platform, because the invitation created the account. New: accepting adds a membership to the existing account. The person also finds the invitation under the bell, written in their own language, and on "Your projects", where they accept or decline it. (`2c8e8b13:app/Http/Requests/CreateMemberRequest.php` unique:users; `app/Services/InvitationService.php` invite/accept, `app/Http/Controllers/InvitationController.php` respond, `app/Http/Controllers/ProjectController.php` index, `tests/UseCases/Membership/InvitationsTest.php` "with an account…", "tells someone who already has an account…")
- **Revoking.** Admins revoke any pending invitation in their project. Anyone else revokes the ones they sent. A revoked link stops working. (`app/Policies/InvitationPolicy.php`, `app/Http/Controllers/Manage/InvitationController.php` and `app/Http/Controllers/Member/InvitationController.php` destroy, `tests/UseCases/Membership/InvitationsTest.php` "revokes a pending invitation")
- **Declining.** The invitee declines from "Your projects", which deletes the invitation. Nobody is notified. (`app/Http/Controllers/InvitationController.php` respond)
- **Lists of pending invitations.** Admins see every pending invitation in the project, with its family and who sent it, and the project's limits while it is Limited. Members see their family's pending invitations. (`app/Http/Controllers/Manage/InvitationController.php` index, `app/Http/Controllers/Member/InvitationController.php` index)
- **Admin and family together, and promotion.** One invitation can make someone an admin and put them in a family. Re-inviting someone already in the project adds what they lack (a family or the admin role). Accepting never moves anyone out of the family they are already in. (`app/Http/Requests/InvitationRequest.php`, `app/Services/InvitationService.php` ensureNotAlreadyIn/accept, `tests/UseCases/Membership/InvitationsTest.php` "never moves someone already in a family")
- **The project hears about it.** When someone accepts, the project gets a "member joined" notification. (`app/Services/InvitationService.php` accept, `tests/UseCases/Membership/InvitationsTest.php` "becomes visible to the project when joining")

### Works differently

- **Invitations are records.** Each one stores the project, the family (optional), the admin flag, the email, the name (optional), the inviter, the expiry and when it was accepted. Old: an invitation was an account flagged "not yet accepted" (`users.invitation_accepted_at`). (`2c8e8b13:app/Models/User.php`; `app/Models/Invitation.php`, `database/migrations/2026_10_27_000001_create_projects_tables.php`)
- **No separate resend; inviting again replaces.** Neither version has a resend button. New: inviting the same address again replaces the pending invitation with a fresh link and expiry, so it works as a resend. Old: a second invitation to the same address was refused, because the email was already taken. (`app/Services/InvitationService.php` invite; `2c8e8b13:app/Http/Requests/CreateMemberRequest.php`)
- **The name is optional when inviting.** Old: first name was required. New: first name and last name are optional, and the invitee fills them in when accepting. (`2c8e8b13:app/Http/Requests/CreateAdminRequest.php`; `app/Http/Requests/InvitationRequest.php`, `app/Http/Controllers/InvitationController.php` createAccount)
- **One invitation email instead of two templates.** Old: separate member and admin emails, written in the deployment's language. New: one email whose opening line depends on the case (family, admin, or both) and which states the expiry date. It is sent to the address directly, not to an account, so it is written in the platform's default language (`APP_LOCALE`, es_UY in the deployment templates), not in the invitee's chosen language. The bell notice, by contrast, is in the invitee's language. (Verified in code, not run.) (`2c8e8b13:app/Notifications/MemberInvitationNotification.php`, `2c8e8b13:app/Notifications/AdminInvitationNotification.php`; `app/Notifications/InvitationNotification.php`, `app/Services/InvitationService.php`, `deploy/env.example`)

## Units, unit types & plan

### No longer possible / newly restricted

- **Some unit changes are locked by the lottery.** Changing a unit's type is refused once a run is running or completed. Creating or deleting units is refused while a run is in progress. Before, nothing about units was locked by the lottery; only assigned units were protected from deletion, and they still are. (`app/Http/Controllers/Manage/UnitController.php`; `2c8e8b13:app/Http/Controllers/Resources/UnitController.php` destroy)
- **Limited projects stop at 50 units.** (`app/Http/Requests/UnitRequest.php` after)
- **Only one admin draws the plan at a time.** The editor holds the plan for 90 seconds and renews the hold every 30. Anyone else is told who is drawing, and a save over another person's hold is refused with 423. Before, the last save won. (`app/Services/Plan/EditingLock.php`; `app/Http/Controllers/Manage/PlanController.php`; `tests/UseCases/ProjectSetup/PlanTest.php` "One person drawing it at a time")
- **Saving the plan cannot create or remove units.** Shapes are units only if they already were; removing a unit's shape in a plan save has no effect. Units come and go only through the units tools. (`app/Http/Requests/PlanRequest.php` after; `app/Services/PlanService.php` update; `tests/UseCases/ProjectSetup/PlanTest.php` "makes no shape a unit unless it is one")

### Newly possible / newly permitted

- **The plan editor draws far more than unit positions.** Before, it only moved the boundary and existing shapes: `UpdatePlanRequest` required every item to exist already, and had no type, floor, name, size or color. Now admins can:
  - add and remove shapes of nine kinds: unit, building, street, green, common, parking, playground, entrance, other;
  - place shapes on floors −5 to 100, and set their order, name and color;
  - change the canvas size (100–10000);
  - add a unit by drawing it on the plan.

  (`2c8e8b13:app/Http/Requests/UpdatePlanRequest.php`; `2c8e8b13:app/Services/Plan/Polygons.php`; `app/Http/Requests/PlanRequest.php`; `app/Services/PlanService.php`; `app/Http/Requests/UnitRequest.php` polygon/floor)
- **A unit type can carry a size on the plan.** New units of that type start at that size; width and height are given together or not at all. (`app/Http/Requests/UnitTypeRequest.php`; `database/migrations/2026_10_29_000003_add_plan_size_to_unit_types.php`; `tests/UseCases/ProjectSetup/UnitTypesTest.php`)
- **Some units wait to be placed.** A unit added from the units page after the site has been drawn waits beside the editor (`placed = false`) instead of landing on the drawing. (`app/Services/PlanService.php` place; `tests/UseCases/ProjectSetup/UnitsTest.php`)

### Works differently

- **Unit validation.** In both versions an identifier is required and unique per project, a type is required (or created on the way) and must be in the same project. Changes:
  - The identifier allows 1–80 characters (was 2–255).
  - Uniqueness is now a validation message instead of a database error.
  - A new type created on the way needs only a name; a description was required before.

  (`2c8e8b13:app/Http/Requests/CreateUnitRequest.php`; `2c8e8b13:app/Http/Requests/UpdateUnitRequest.php`; `app/Http/Requests/UnitRequest.php`)
- **Unit type validation.** The name allows 2–80 characters (was 2–255) and is checked unique per project (only the database did before). The description is optional, up to 500 characters; it was *required*, 2–255. (`2c8e8b13:app/Http/Requests/CreateUnitTypeRequest.php`; `app/Http/Requests/UnitTypeRequest.php`)
- **Deleting a unit type.** Both versions refuse to delete a type that units or families use. Before, the delete was soft and a restore method existed but no route led to it; now it is final. Type changes on families and units lock after the lottery, but renaming a type does not. (`2c8e8b13:app/Http/Controllers/Resources/UnitTypeController.php` destroy; `app/Http/Controllers/Manage/UnitTypeController.php`; `app/Models/UnitType.php` isInUse)
- **Deleting a unit.** Before, the unit was soft-deleted and its plan shape stayed, because the unit-to-shape link blocked removing the shape; this is inferred from the schema and not exercised. Now the unit is deleted for good and its shape goes with it. (`2c8e8b13:database/migrations/2025_11_16_200232_create_plan_items_table_and_add_units_fk.php`; `database/migrations/2026_10_27_000002_create_plans_tables.php` plan_items.unit_id cascade)
- **Only the lottery assigns units to families.** No form in either version assigns one by hand (`UpdateUnitRequest` and `UnitRequest` have no `family_id`). A family can now hold at most one unit (`units.family_id` unique); before, the database allowed more. (`2c8e8b13:database/migrations/2025_07_10_131515_create_units_table.php`; `database/migrations/2026_10_27_000001_create_projects_tables.php`)
- **Balance between units and families is shown, not enforced.** "As many units as families per type" now appears as a setup step for admins. Outside the lottery's own mismatch check it was not enforced before, and still is not. Families and units can still be added after the lottery completed; only a running lottery blocks that. (`app/Services/ProjectService.php` balanced; `app/Http/Controllers/Manage/FamilyController.php`; `app/Http/Controllers/Manage/UnitController.php`)
- **Who sees the plan is unchanged.** Everyone in the project sees it: before on the lottery page, now on the units page and the lottery page. Only admins edit it, in both versions. Editing the plan is never locked by the lottery, before or now. (`2c8e8b13:app/Http/Controllers/LotteryController.php`; `2c8e8b13:app/Policies/PlanPolicy.php`; `app/Http/Controllers/Member/UnitController.php`; `app/Policies/PlanPolicy.php`)
- **New units still land on the plan by themselves.** Before, `UnitObserver` did this; now `PlanService::place` does, from the controller, where the unit was drawn or in the next free position. (`2c8e8b13:app/Observers/UnitObserver.php`; `app/Http/Controllers/Manage/UnitController.php`)
- **Unit types have no pages of their own.** Before, they had index and show pages for everyone (show listed units and families); now they are listed on the units page. (`2c8e8b13:app/Http/Controllers/Resources/UnitTypeController.php`; `app/Http/Controllers/Member/UnitController.php`)

## Lottery (business rules around the solver)

Unchanged, checked at both commits: the lottery is a project event created with the project, published, titled "Lottery" (`2c8e8b13:app/Services/LotteryService.php` createLotteryEvent, `app/Services/ProjectService.php`); it runs only once a date is set and has passed, and with at least 2 families; a per-type mismatch of units vs families asks the admin to confirm (`mismatch-allowed` option) and then runs anyway; families rank only units of their own type; every project user sees which unit each family received; invalidation clears every unit assignment of the project and keeps the families' rankings; after an invalidation the lottery can run again.

### No longer possible / newly restricted

- Old: running the lottery had no size cap → New: in a Limited (unapproved) project a run with more families than `limits.unapproved.families` (default 50) is refused ("too_large"). (`2c8e8b13:app/Services/Lottery/ExecutionService.php`; `app/Services/LotteryService.php` ensureCanRun; `config/limits.php`; test `tests/UseCases/Lottery/ExecutionTest.php` "cannot run an unapproved project's lottery beyond its size limit")
- Old: an admin could start a run with no queue worker alive; it then stayed "executing" indefinitely (preferences and lottery locked, UI only showed "taking longer than expected" after 60 s) → New: running is refused while no worker has been seen in the last 120 s, and the lottery page tells everyone the server cannot run it now. (`2c8e8b13:app/Listeners/Lottery/ExecuteLotteryListener.php` ShouldQueue, `2c8e8b13:resources/js/components/lottery/shared/Audit.vue`; `app/Services/Queue/Workers.php`, `app/Http/Controllers/Member/LotteryController.php` `runnable`, `tests/UseCases/Lottery/WorkersTest.php`)
- Old: a superadmin could invalidate the lottery at any moment, including while it was still executing (route `withTrashed`, no state check) → New: only a *completed* run can be invalidated (409 otherwise). (`2c8e8b13:routes/web/project.php`, `2c8e8b13:app/Services/Lottery/ExecutionService.php` invalidate; `app/Http/Controllers/Manage/LotteryController.php` invalidate)
- Old: invalidation needed no explanation → New: a reason of 10–1000 characters is mandatory. (`2c8e8b13:app/Http/Requests/InvalidateLotteryRequest.php`; `app/Http/Controllers/Manage/LotteryController.php`; test "cannot invalidate without a reason")
- Old: nothing else in the project froze during a run → New: while a run is solving, admins cannot add/remove families or add/remove units; while a run is running *or completed*, a family's or a unit's unit type cannot change. (`app/Models/Project.php` ensureLotteryNotRunning/lotteryLocked; `app/Http/Controllers/Manage/FamilyController.php`, `app/Http/Controllers/Manage/UnitController.php`; test "holds units and families still while a run is being solved")
- Old: the lottery title was hidden in the edit form but the generic event-update endpoint accepted a new `title` for the lottery event → New: the title is fixed; the lottery is edited only from the lottery page (date, description) and the event endpoints answer 404/deny for it. (`2c8e8b13:app/Http/Requests/UpdateEventRequest.php` + `2c8e8b13:app/Services/LotteryService.php` updateLotteryEvent `Arr::only(['title',…])`; `app/Http/Controllers/Manage/EventController.php` update, `app/Policies/EventPolicy.php`)
- Old: once a run started, the lottery page payload carried the audits, whose INIT record holds every family's complete ranking (and the admin's email), so any member could read other families' preferences in the page data → New: audits never reach the browser; each family gets only its own ranking. (`2c8e8b13:app/Http/Controllers/LotteryController.php` index `with('audits')`, `2c8e8b13:app/Services/Lottery/DataObjects/LotteryManifest.php`; `app/Http/Controllers/Member/LotteryController.php`, `tests/UseCases/Lottery/PreferencesTest.php` "never shows a family the ranking of another family")

### Newly possible / newly permitted

- Old: only superadmins invalidated a lottery → New: project admins (and superadmins) invalidate a completed run. (`2c8e8b13:app/Http/Requests/InvalidateLotteryRequest.php` isSuperadmin; `routes/web.php` manage group `can:update,project`, `app/Policies/ProjectPolicy.php` invalidateLottery)
- Old: invalidation left no visible trace (only an INVALIDATE audit, plus generic "event restored/updated" notifications) → New: every project member sees each run on the lottery page — invalidated runs with who, when and why — and gets a project notification naming the admin and the reason. (`2c8e8b13:app/Services/Lottery/ExecutionService.php`; `app/Http/Resources/LotteryRunResource.php`, `app/Services/LotteryService.php` invalidate, test "invalidates a completed lottery, giving a reason every member can read")
- Old: a family had to submit a ranking of *all* units of its type (the service threw on a missing unit; the UI always posted the full list) → New: a family ranks as many units as it likes (even none, which clears its ranking); the rest follow in a fixed default order. (`2c8e8b13:app/Services/Lottery/PreferencesService.php` validateBeforeUpdate; `app/Services/Lottery/PreferencesService.php` rank, `app/Http/Controllers/Member/LotteryController.php` `preferences: present|array`)
- Old: admins could never rank (they could not belong to a family; `UpdateLotteryPreferencesRequest` required `isMember()`) → New: anyone with a family in the project ranks for it, so an admin who is also in a family ranks too; superadmins without a family still cannot. (`2c8e8b13:app/Http/Requests/UpdateLotteryPreferencesRequest.php`; `app/Policies/ProjectPolicy.php` rankUnits, `AppServiceProvider` PERSONAL_ABILITIES)
- New: admins see a readiness table (units vs families per type) before running. (`app/Http/Controllers/Member/LotteryController.php` `admin.readiness`; none in `2c8e8b13:resources/js/components/lottery/admin/LotteryManagement.vue`)
- New: superadmins set, in the platform console, the minimum time a run is shown "running" (default 20 s, 0–300 s). (`app/Models/PlatformSetting.php`, `app/Http/Controllers/Platform/SettingsController.php`, `tests/UseCases/Lottery/PaceTest.php`)
- New: the history of all runs (running, completed, failed with its message, invalidated) stays listed for everyone; old re-runs soft-deleted the previous audits. (`2c8e8b13:app/Services/Lottery/AuditService.php` init; `app/Models/LotteryRun.php`, test "runs the lottery again after an invalidation, keeping the earlier run on record")

### Works differently

- Execution flow. Old: an event (`LotteryExecutionTriggered`) → queued listener runs the orchestrator → per-group and project events → queued listeners write audits and apply results (unit updates in a loop, no transaction) and soft-delete the lottery event as the "completed" mark; the lottery event's `is_published=false` meant "executing". New: a `lottery_runs` record (running/completed/failed/invalidated) per execution; `RunLottery` job solves, the assignment is written in one transaction. (`2c8e8b13:app/Services/Lottery/ExecutionService.php`, `2c8e8b13:app/Listeners/Lottery/*.php`; `app/Jobs/RunLottery.php`, `app/Services/LotteryService.php` complete, `app/Enums/LotteryRunStatus.php`)
- Pacing. Old: results appeared as soon as solved (UI estimated progress, polled every 0.5 s). New: the result is held on the run and written at `reveal_at` (start + the platform minimum) by `PublishLotteryRun`; until then every page shows the lottery running, then the result everywhere at once. (`app/Services/LotteryService.php` solved, `app/Jobs/PublishLotteryRun.php`, `tests/UseCases/Lottery/PaceTest.php`)
- Failure. Old: a failure wrote a FAILURE audit (shown to all viewers with error type and exception class) and silently reopened the lottery; nobody was notified. New: the run becomes *failed* with a user-facing message shown in the runs list, the admin who started it gets a private notification, a run whose job dies or exceeds 600 s also fails, and the lottery can run again. (`2c8e8b13:app/Services/Lottery/LotteryOrchestrator.php` handleExecutionFailure; `app/Services/LotteryService.php` fail, `app/Jobs/RunLottery.php`, tests "closes a run that fails, tells whoever started it…", "closes a run whose job died…")
- Completion. Old: no notification on completion (results applied by query-builder updates, so the model observer never fired); the lottery event was soft-deleted and vanished from the events list. New: a project notification "lottery completed" goes to everyone; the lottery event stays. (`2c8e8b13:app/Services/Lottery/ExecutionService.php` applyResults; `app/Services/LotteryService.php` complete)
- Preference lock. Old: locked while the lottery event was unpublished (executing); after completion the event was soft-deleted, so the lock check hit a missing event (unverified: likely a server error instead of the lock message). New: locked while a run is running or completed (`lotteryLocked`), with a clear message; the page hides ranking (`canRank`). (`2c8e8b13:app/Services/Lottery/PreferencesService.php`; `app/Models/Project.php`, `app/Services/Lottery/PreferencesService.php`, test "cannot be changed once the lottery has started")
- Default order of unranked units. Old: shuffled randomly and *saved* into the family's ranking (positions ≥ 1000) the first time it was read, so it then looked like the family's own order. New: not saved; computed each time from a hash of app key + family + unit, random-like but fixed per family. (`2c8e8b13:app/Services/Lottery/PreferencesService.php` addMissingPreferences; `app/Services/Lottery/PreferencesService.php` defaultOrder)
- Rankings of units no longer of the family's type. Old: deleted on read and an `InvalidPreferences` event dispatched (no listener: nothing else happened). New: ignored, left stored. (`2c8e8b13:app/Events/InvalidPreferences.php`; `app/Services/Lottery/PreferencesService.php` ranking)
- Scheduling. Old: admins edited the lottery's date/description from the lottery page or the event edit page; description 2–255 chars. New: only from the lottery page, description 2–5000 chars, locked once a run is running/completed. (`2c8e8b13:app/Http/Requests/UpdateLotteryRequest.php`, `2c8e8b13:app/Http/Controllers/Resources/EventController.php`; `app/Http/Controllers/Manage/LotteryController.php` schedule, `app/Services/LotteryService.php` schedule)
- Solver. Old: default `random` unless `LOTTERY_SOLVER` was set (the old deployment set it to `glpk`). New: default `glpk`; random/test only when chosen; the solver used is recorded on each run. (`2c8e8b13:config/lottery.php`; `config/lottery.php`, `app/Services/LotteryService.php` run)
- Audit. Old: rows keyed by execution UUID + project + lottery event, soft-deletable, earlier ones soft-deleted on each new execution, project/event deletion restricted while audits exist. New: rows per run, never soft-deleted, kept as long as the project (deleted with it for good); same record types; INIT holds the admin and full manifest, INVALIDATE the admin and reason. (`2c8e8b13:database/migrations/2025_12_02_020431_create_lottery_audits_table.php`, `2c8e8b13:app/Services/Lottery/AuditService.php`; `database/migrations/2026_10_27_000004_create_lottery_tables.php`, `app/Services/Lottery/AuditService.php`)
- Who runs it. Old: any admin (`isAdmin()`, superadmins included) of a project in their scope. New: the project's admins and superadmins (manage middleware). Effectively the same people. (`2c8e8b13:app/Http/Requests/ExecuteLotteryRequest.php`; `routes/web.php`)
- Concurrency. Old: a conditional update on the event reserved it. New: a row lock on the project and "one active run" rule. Same observable rule: one run at a time, once. (`2c8e8b13:app/Services/Lottery/ExecutionService.php` reserveLotteryForExecution; `app/Services/LotteryService.php` run, test "runs the lottery only once")

## Files

Unchanged, checked at both commits: any project user shares files with a description (2–255 chars); only the uploader edits a file's details; the uploader or an admin removes it; search covers description and uploader name; images and videos get thumbnails.

### No longer possible / newly restricted

- Old: files lived on the public disk at `/storage/media/<uuid>.<ext>`: anyone with the link (guests, other projects) could open them, and a "deleted" file stayed reachable because deletion was a soft delete that kept the file. New: files sit on a private disk, streamed only to people with access to the project (404 otherwise); deleting removes the file and its thumbnail for good. (`2c8e8b13:app/Services/MediaService.php` storeAs 'public', `2c8e8b13:app/Http/Controllers/Resources/MediaController.php` destroy; `config/filesystems.php` disk `media`, `app/Http/Controllers/Member/MediaFileController.php`, `app/Http/Middleware/ResolveProject.php`, `app/Services/MediaService.php` delete, test `tests/UseCases/Community/MediaTest.php` "keeps a project's files away from other projects and from guests")
- SVG no longer accepted (old accepted and used the SVG itself as thumbnail); AVI (`video/x-msvideo`) no longer accepted. (`2c8e8b13:app/Http/Requests/StoreMediaRequest.php` mimes; `app/Services/MediaService.php` MIME_TYPES, test "rejects SVG images")
- Old: files of unrecognized content were stored under category `unknown` → New: only listed types are accepted, checked by content (`mimetypes`); stored under the extension guessed from content, not the client's name. (`2c8e8b13:app/Enums/MediaCategory.php`, `2c8e8b13:app/Services/MediaService.php` mime2category/getClientOriginalExtension; `app/Http/Requests/MediaRequest.php`, `app/Services/MediaService.php` storeOne)
- New: images over 50 megapixels are refused (also for avatars). (`app/Services/MediaService.php` MAX_MEGAPIXELS, test "refuses an image of more pixels than can be opened")
- New: at most 20 files per share, and a share is bounded by the server's request size. (`app/Http/Requests/MediaRequest.php` MAX_FILES, `app/Services/MediaService.php` maxBatchKb)
- Limited projects: 25 MB per file and 500 MB in all (usage shown on the media page); old had no total. (`config/limits.php`, `app/Services/MediaService.php` ensureStorageAvailable, `app/Http/Controllers/Member/MediaController.php` storage)
- Old: no restore of deleted files reachable (controller method, no route) but records were soft-deleted, so recoverable by hand → New: deletion is final. (`2c8e8b13:routes/web/project.php`; `database/migrations/2026_10_27_000003_create_community_tables.php` media without softDeletes)
- Avatars: old any image up to 2 MB stored as uploaded → New: JPEG/PNG/WebP only, up to 8 MB and 50 MP, redrawn at most 512 px as WebP (location metadata dropped). (`2c8e8b13:app/Http/Requests/Settings/AvatarUpdateRequest.php`; `app/Http/Controllers/Settings/AvatarController.php`)

### Newly possible / newly permitted

- Per-file size for Unlimited projects rises from 100 MB to 200 MB (still capped by PHP's upload limit). (`2c8e8b13:app/Http/Requests/StoreMediaRequest.php` max:102400; `config/limits.php` media_file_max_mb, `app/Services/MediaService.php` maxFileKb)
- New accepted types: audio m4a/mp4 (phone voice memos), ogg, webm audio; OpenDocument text and spreadsheet. (`app/Services/MediaService.php` MIME_TYPES, test "files a phone's voice memo under audio")
- The owner can now edit the alt text as well as the description. (`2c8e8b13:app/Http/Requests/UpdateMediaRequest.php`; `app/Http/Controllers/Member/MediaController.php` update)
- A file now outlives its uploader's account (owner set to null); old deleted the uploader's files with the account. (`2c8e8b13:database/migrations/2025_11_14_172313_create_media_table.php` cascadeOnDelete; `database/migrations/2026_10_27_000003_create_community_tables.php` nullOnDelete)

### Works differently

- Categories. Old: separate pages per kind — gallery (images+videos), audios, documents. New: one media page; its default tab holds images, videos *and audio*, documents have their own tab; `unknown` and `visual` are gone, a non-stored `media` filter is added. (`2c8e8b13:routes/web/project.php`, `2c8e8b13:app/Enums/MediaCategory.php`; `app/Enums/MediaCategory.php`, `app/Models/Media.php` scopeCategory)
- Who may upload. Old: any authenticated user in the current project (policy `create` true). New: anyone with a membership in the project (members and admins), superadmins via the gate. (`2c8e8b13:app/Policies/MediaPolicy.php`; `app/Policies/MediaPolicy.php`)
- Who may delete. Old: owner or anyone with `isAdmin()` (an admin of any project in their scope, or superadmin). New: owner or an admin of *that* project (or superadmin). (`2c8e8b13:app/Policies/MediaPolicy.php`; `app/Policies/MediaPolicy.php`, test "lets the owner and admins remove a file")
- Serving. New serves images, video, audio and PDF inline and everything else as a download, with `nosniff`, download names derived from the description. (`app/Http/Controllers/Member/MediaFileController.php`)
- Thumbnails. Old: 800 px JPEG, animated GIFs and SVGs shown as themselves, placeholder images for audio/other files. New: 480 px JPEG for images and videos only. (`2c8e8b13:app/Services/MediaThumbnailService.php`; `app/Services/MediaThumbnailService.php`)
- Photos and videos open in a carousel that moves across pages of the list. (`app/Http/Controllers/Member/MediaController.php` carousel)

## Events

Unchanged, checked at both commits: only admins create, edit and delete events; types onsite/online (+ lottery, system-only); drafts visible to admins only, published ones to the whole project; an online event's location must be a URL; the end must be after the start; implicit duration 60 min; the lottery event exists from project creation and cannot be deleted.

### No longer possible / newly restricted

- Old: events were soft-deleted → New: deleting an event removes it and its answers for good. (`2c8e8b13:database/migrations/2025_11_08_080322_create_events_table.php`; `database/migrations/2026_10_27_000003_create_community_tables.php`)
- Title limit drops from 255 to 160 characters; online-event links must be http/https. (`2c8e8b13:app/Http/Requests/CreateEventRequest.php`; `app/Http/Requests/EventRequest.php`)
- The lottery event can no longer be edited through the events pages (404/denied), only scheduled on the lottery page. (`2c8e8b13:app/Http/Controllers/Resources/EventController.php` edit/update; `app/Http/Controllers/Manage/EventController.php`, `app/Policies/EventPolicy.php`, test `tests/UseCases/Community/EventsTest.php` "cannot edit the lottery event as an event")

### Newly possible / newly permitted

- RSVP. Old: the schema had an `rsvp` flag and an `event_rsvp` table and the UI showed counts, but there was no route to answer and the create/update requests dropped the flag, so admins could not enable it nor members answer (the thesis describes it as working). New: admins mark an event as asking for attendance; anyone with a membership in the project (members, and admins who have one) answers attending / not attending on a published, non-lottery event, and may change the answer; the attendees' names are shown to everyone who sees the event. Per person, not per family; no deadline (answers are accepted even after the event). (`2c8e8b13:routes/web/project.php`, `2c8e8b13:app/Http/Requests/CreateEventRequest.php`, `documentation/thesis/parte-2-descripcion-funcional.md` §6.4; `routes/web.php` events.rsvp, `app/Http/Controllers/Member/EventController.php` rsvp, `app/Policies/EventPolicy.php` rsvp, `app/Http/Resources/EventResource.php` attendees, tests "confirms or declines attendance…", "cannot answer an event that does not ask for it")
- Descriptions up to 5000 characters (old 255). (`app/Http/Requests/EventRequest.php`)
- The event's creator is recorded and shown (old `store` never set `creator_id`). (`2c8e8b13:app/Http/Controllers/Resources/EventController.php` store; `app/Http/Controllers/Manage/EventController.php` store, `app/Http/Resources/EventResource.php`)

### Works differently

- Listing. Old: one list, lottery first then latest start date first. New: "upcoming" (undated, future or under way; soonest first) and "past" (latest first) views, lottery first. (`2c8e8b13:app/Models/Event.php` scopeSorted; `app/Models/Event.php`, `app/Http/Controllers/Member/EventController.php`, test "sees the coming events soonest first, and past ones latest first")
- The lottery event. Old: hidden from members while executing (unpublished) and gone from all lists once completed (soft-deleted). New: always present and published; the home page lists upcoming events without it and shows the lottery apart. (`2c8e8b13:app/Services/Lottery/ExecutionService.php`; `app/Http/Controllers/Member/DashboardController.php`)
- Who manages. Old: any `isAdmin()` user over events of projects in their scope. New: admins of that project (and superadmins). (`2c8e8b13:app/Policies/EventPolicy.php`; `app/Policies/ProjectResourcePolicy.php`, `app/Policies/EventPolicy.php`)
- Creating, editing or deleting an event no longer notifies anyone (old notified the whole project, drafts included, leaking draft titles to members); open pages refresh silently instead. (`2c8e8b13:app/Services/Notifications/ResourceLifecycle.php`; `app/Models/Concerns/BroadcastsProjectChanges.php`)
- Deleting a creator's account no longer blocks (old `restrictOnDelete` on creator); the event stays with no creator. (`2c8e8b13:database/migrations/2025_11_08_080322_create_events_table.php`; new community migration)

## Notifications

Unchanged, checked at both commits: in-app notifications addressed to one person (private), a project, or global; read/unread per person, mark one read/unread, mark all read; a person sees only notifications created after their account was; none of them is emailed (mail is only for invitations, password resets, email verification, and new: email-change notices).

### No longer possible / newly restricted

- Resource notifications are gone. Old: every create/update/delete/restore of an event, family, file, project, unit or unit type (except the lottery event's creation) created a project notification for everyone in it ("X created event Y"), drafts included; project create/delete/restore went global. New: none of these; only the specific notifications listed below. (`2c8e8b13:app/Observers/ModelObserver.php`, `2c8e8b13:app/Services/Notifications/ResourceLifecycle.php`; `app/Services/NotificationService.php` callers)
- Old: global notifications reached admins with more than one project and superadmins → New: superadmins only. (`2c8e8b13:app/Models/Notification.php` scopeForUser, `2c8e8b13:app/Policies/NotificationPolicy.php`; `app/Models/Notification.php` scopeFor/isFor)
- Old: superadmins saw every project's notifications → New: only those of projects where they hold a membership (plus global and their own). (`2c8e8b13:app/Models/Notification.php` scopeForUser; `app/Models/Notification.php` scopeFor)
- Old: the project channel accepted any signed-in user (its authorization returned user data without checking access) → New: only people with access to the project; each person only their own channel. (`2c8e8b13:routes/channels.php`; `routes/channels.php`, test `tests/UseCases/RealTime/LiveUpdatesTest.php`)
- New: notifications of archived projects drop out of everyone's list. (`app/Models/Notification.php` scopeFor)
- New: on environments that set `MAIL_BLOCKED_DOMAINS` (staging: the demo accounts' reserved domains), mail to those addresses is dropped and logged; a message left with no recipient is not sent. (`app/Listeners/SkipBlockedRecipients.php`, `config/mail.php`)

### Newly possible / newly permitted

- New specific notifications: lottery completed (project), lottery failed (private, to the admin who started it), lottery invalidated with admin and reason (project), member joined (project), invitation received (private, for existing accounts, in the invitee's language), project created and approval requested (global, superadmins), project approved (project). (`app/Services/LotteryService.php`, `app/Services/InvitationService.php`, `app/Services/ProjectService.php`, `app/Enums/NotificationType.php`)
- Real-time delivery actually works: each notification is pushed to the person's, the project's or the superadmins' channel, and every change to a project's records announces itself so open pages refresh; delivery is best effort (a down websocket server never fails the action). Old had the frontend listeners and a `BroadcastService`, but nothing on the server ever broadcast (no caller; `ResourceCreated` never dispatched), so updates arrived only on page load; the lottery page polled. (`2c8e8b13:app/Services/BroadcastService.php`, `2c8e8b13:app/Events/ResourceCreated.php`; `app/Events/NotificationSent.php`, `app/Events/ProjectChanged.php`, `app/Models/Concerns/BroadcastsProjectChanges.php`)
- Opening a notification marks it read and goes straight to what it is about; the list can show unread only. (`app/Http/Controllers/NotificationController.php` read/index)

### Works differently

- Project notifications follow memberships: someone who joins a further project with an existing account sees that project's notifications back to their account's creation, not to their joining (old accounts were created by the invitation into their one project). (`app/Models/Notification.php` scopeFor `created_at >= user.created_at`; `2c8e8b13:app/Models/Notification.php` sinceRegistration)
- Old `InvalidPreferences` event (dispatched when a family's stored ranking held units outside its type) had no listener and did nothing observable; removed. Old `UserRegistration` event carried the new account and its password/token to the `SendUserInvitation` listener, which mailed the invitation; replaced by an invitation record mailed directly by `InvitationService`. (`2c8e8b13:app/Events/InvalidPreferences.php`, `2c8e8b13:app/Events/UserRegistration.php`, `2c8e8b13:app/Listeners/SendUserInvitation.php`; `app/Services/InvitationService.php`)
- Unused old types (unit assigned, RSVP confirmed, event reminder, construction update, milestone, news, system announcement/maintenance, registration confirmed) are dropped; none was ever sent at either commit. (`2c8e8b13:app/Enums/NotificationType.php`; `app/Enums/NotificationType.php`)

## Platform / superadmin

### No longer possible / newly restricted

- **Superadmins no longer pass every check.** Old: superadmins passed every permission check. New: they pass all except ranking units, answering RSVPs and deploying. Deploying requires being listed in `DEPLOYERS` and GitHub being configured. (`2c8e8b13:app/Providers/AppServiceProvider.php`; `app/Providers/AppServiceProvider.php` PERSONAL_ABILITIES)
- **Superadmins can no longer delete or restore accounts.** Old: through the general permission bypass, superadmins could delete any member or admin and restore deleted admins (`admins.restore`). New: the console's Accounts page only lists and searches accounts (name, email, superadmin, email confirmed, number of projects). Nobody can block, delete or restore an account. People are removed from projects one membership at a time. (`2c8e8b13:routes/web/users.php`, `2c8e8b13:app/Http/Controllers/Resources/AdminController.php` restore; `app/Http/Controllers/Platform/UserController.php`, `resources/js/pages/Platform/Users.tsx`)
- **A site with no account stays closed.** New: while there is no account and no `OWNER_EMAIL`/`OWNER_PASSWORD`, every page shows "under construction" (503). (`app/Http/Middleware/RequireOwner.php`, `tests/UseCases/Platform/OwnerTest.php`)

### Newly possible / newly permitted

- **The superadmin role is stored on the account.** Old: superadmins were the accounts whose email was in the `SUPERADMINS` environment list (default `superadmin@example.com`) and which also had `is_admin` set. New: `users.is_superadmin`. It is granted or revoked with `php artisan mtav:superadmin {email} [--revoke]`, or in the console's Superadmins page, by the account's exact email. A superadmin cannot remove their own role. (`2c8e8b13:config/auth.php` superadmins, `2c8e8b13:app/Models/User.php` isSuperadmin; `app/Console/Commands/GrantSuperadmin.php`, `app/Http/Controllers/Platform/UserController.php` grant/superadmin, `tests/UseCases/Platform/SuperadminsTest.php`)
- **An owner per deployment.** On the first request to an empty site, `OWNER_EMAIL`, `OWNER_PASSWORD` and `OWNER_NAME` create the first superadmin (`is_owner`, email already confirmed). Where demo data is seeded, the first seeded account is the owner. `is_owner` grants nothing more today: another superadmin can remove the owner's role. (`app/Http/Middleware/RequireOwner.php`, `config/app.php` owner, `database/seeders/DatabaseSeeder.php`, `app/Http/Controllers/Platform/UserController.php` superadmin)
- **The platform console at `/admin`.** Superadmins only; to anyone else it is a 404. Superadmins land there when they sign in.
  - **Projects:** filtered by awaiting approval, unapproved, approved or archived, and searchable. Projects awaiting approval come first. Each shows its creator and counts (people, families, units, events, files and their size, lottery runs).
  - **Approving:** turns a project from Limited to Unlimited and notifies the project.
  - **Archived projects:** restore one, or delete it for good after typing its name.
  - **Accounts.**
  - **Superadmins.**
  - **UI Lab.**
  - **Settings:** how long a lottery run takes as people see it, 0–300 s, default 20.
  - **Deployments:** shown to deployers only. Deploy dev from a branch, deploy production from `main`, or roll back.

  (`routes/web.php` admin group, `app/Http/Middleware/EnsureSuperadmin.php`, `app/Http/Controllers/Platform/*.php`, `app/Models/PlatformSetting.php`, `tests/UseCases/Platform/DeploymentsTest.php`, `tests/UseCases/Platform/ArchiveTest.php`)
- **Superadmins hear about the platform.** New projects and approval requests reach superadmins as notifications, live on a `superadmins` channel. Project admins request approval with "Limited → ask for unlimited", up to 3 times an hour. (`app/Services/ProjectService.php` create/requestApproval, `routes/channels.php`, `routes/web.php` approval.request, `app/Models/Notification.php` scopeFor)

### Works differently

- **Projects and approval.** Old: only superadmins created projects, and there was no notion of approval. New: anyone with a confirmed email creates a project. It starts Limited unless a superadmin created it, and the superadmin who creates one is not added as a member. Approval cannot be withdrawn: there is no route that makes a project Limited again. (Detail is in the projects section.) (`2c8e8b13:app/Policies/ProjectPolicy.php` create; `app/Policies/ProjectPolicy.php`, `app/Services/ProjectService.php` create/approve)
- **Superadmins reach every project without being members.** They see all projects in the project switcher, and the `global` channel became `superadmins`. Old: the `global` channel was for admins with more than one project. (`2c8e8b13:routes/channels.php`; `app/Models/User.php` reachableProjects, `routes/channels.php`)

## Public pages

### No longer possible / newly restricted

- **The "Contact admin" page is gone.** It was a placeholder: an admin card linked to `/contact/{admin}`, which showed only "TODO". New: there is no contact page. People reach each other through contact details that are shared or seen by admins. (`2c8e8b13:app/Http/Controllers/ContactController.php`, `2c8e8b13:resources/js/pages/Admins/Contact.vue`, `2c8e8b13:resources/js/components/entities/admin/AdminCardFooter.vue`; `routes/web.php`)

### Newly possible / newly permitted

- **A landing page.** Old: `/` was the signed-in dashboard, and a guest saw only the login page. New: guests get a landing page that presents MTAV with photos of cooperatives. Sign in and sign up open as dialogs over it (`/login`, `/register`). It has a language switch, Google sign-in where configured, and a link to the manual. (`2c8e8b13:routes/web/project.php` dashboard; `app/Http/Controllers/HomeController.php`, `resources/js/pages/Welcome.tsx`, `tests/UseCases/Accounts/WelcomeTest.php`)
- **A public member manual at `/manual`.** Anyone can read it, signed in or not, in their browser's language or the one they picked. (`app/Http/Controllers/DocumentationController.php` manual, `tests/UseCases/Accounts/ManualTest.php`)
- **A friendly error page.** Old: being refused a page sent the person silently to the dashboard, and other errors showed the framework's page. New: 403, 404, 500 and 503 show an error page inside the app. A refused form shows a "not allowed" message on the page it came from, and an upload that is too large says so. (`2c8e8b13:bootstrap/app.php`; `bootstrap/app.php`, `routes/web.php` fallback)

### Works differently

- **FAQ and guide still need sign-in.** Old: their route comment said "accessible to everyone", but the routes sat behind `auth`. New: they are still behind `auth`; only the member manual is public. The role is now worked out per membership: someone who is an admin in any project gets the admin FAQ and guide, and superadmins get their own guide. (`2c8e8b13:routes/web.php` + `2c8e8b13:routes/web/documentation.php`; `routes/web.php`, `app/Http/Controllers/DocumentationController.php`)
- **No legal pages in either version.** No terms or privacy page was found in old or new. (Checked old `resources/js/pages`, new `routes/web.php` and `resources/js/pages/Welcome.tsx`.)

## Other: languages

### No longer possible / newly restricted

- Nothing restricted.

### Newly possible / newly permitted

- **Five languages instead of two.** Old: English and Spanish (es_UY). New: English, Spanish, French, Portuguese (Brazil) and Italian, each with its words and guides. Tests refuse a language that is missing a word, a blank or a guide. (`2c8e8b13:lang/` has en, es_UY; `config/app.php` locales, `resources/views/guides/{en,es_UY,fr,it,pt_BR}`, `tests/Arch/LanguagesTest.php`)
- **Each person picks their language.** Old: one language per deployment (`APP_LOCALE=es_UY`), with no switch anywhere. New: guests switch language on the landing page, and the choice lasts the visit. When they sign in, it becomes their account's language. Signed-in people change it in their preferences. (`2c8e8b13:.env.template`; `app/Http/Controllers/LanguageController.php`, `app/Http/Middleware/SetLocale.php`, `tests/UseCases/Accounts/LanguageTest.php`)

### Works differently

- **How the language is chosen.** In order: the person's pick, then the browser's language if the platform has it (matched by language family, so es-AR finds Spanish), then English. `APP_LOCALE` is no longer the fallback for pages. Someone who never picked keeps the language found the first time, and their mail uses it too. (`app/Http/Middleware/SetLocale.php`)
- **Mail is in the reader's language.** Old: mail used the deployment's language. New: mail sent to an account (confirmation, password reset, email change) uses that account's language, including the template's own words. Invitation emails are the exception (see Invitations). (`2c8e8b13:app/Notifications/Concerns/LocalizableEmail.php`; `app/Models/User.php` preferredLocale, `tests/UseCases/Mail/LanguageTest.php`)
- **Project notifications are in the actor's language.** Their text is fixed when they are created, in the language of whoever caused them, so readers who use other languages see it in that one. The exception is the "invited" notice, which is written in the invitee's language. Notifications translated per reader are a later item. (`app/Services/ProjectService.php`, `app/Services/InvitationService.php`, `documentation/planning/refactor.md` item 19)

## Other: offline & live updates

### No longer possible / newly restricted

- **No automatic CSRF refresh.** Old: login and logout were exempt from the CSRF check, and when a request failed with 419 (expired page), the app fetched a new token from `/csrf-token` and retried. New: there is no such route, no exemption and no automatic retry. Unverified: what a person sees after a 419 in the new app was not checked in a browser. (`2c8e8b13:bootstrap/app.php`, `2c8e8b13:routes/web/auth.php`, `2c8e8b13:resources/js/composables/useCsrfToken.ts`; `bootstrap/app.php`, `routes/web.php`)
- **Only a project's people can listen to its live channel.** Old: any signed-in user could join any project's presence channel, which exposed everyone's id and name. New: only people of the project (and superadmins) can join, and only you can listen to your own notifications channel. (`2c8e8b13:routes/channels.php`; `routes/channels.php`, `tests/UseCases/RealTime/LiveUpdatesTest.php`)
- **Offline, no change is sent.** While offline, adding, editing, uploading, removing, ranking or answering an event is refused with a "You are offline, nothing was sent" message. Changes are not queued for later. (`resources/js/lib/offline.ts` heldOffline)

### Newly possible / newly permitted

- **Working offline.** A service worker keeps each page as it was last loaded and serves it when the server cannot be reached. A project's main pages are loaded in the background on arrival, so they open offline even if never visited. The bar says when the app is offline. Another person signing in on the same device starts afresh. Old: no service worker. (`public/sw.js`, `resources/js/lib/offline.ts`, `resources/js/components/layout/OfflineStatus.tsx`, `resources/js/layouts/ProjectLayout.tsx`; old `public/` has no sw.js)
- **Live updates you can see.** Open pages reload the data someone else changed, and notifications appear as they arrive. Old: the server broadcast changes, but no component listened: `useBroadcasting` and `useNotifications` had no callers (checked with grep). If the websocket server is down, every action still works. (`resources/js/lib/realtime.ts`, `tests/UseCases/RealTime/LiveUpdatesTest.php`; `2c8e8b13:resources/js/composables/useBroadcasting.ts`, `2c8e8b13:resources/js/composables/useNotifications.ts`)
- **Guided tours and (?) help.** Each page has a (?) by its title that explains the page in a short paragraph and offers a tour. For new people, tours start by themselves on each page. After the first one is dismissed, the app asks once whether to stop showing them. What has been seen is remembered on the device. Old: nothing like this. (`resources/js/lib/tour.ts`, `resources/js/tours/index.ts`)

### Works differently

- **Quick actions ("+" menu).** Old: a "Quick Actions" menu with New Project, New Unit, New Admin, New Family, New Member, New Event, Upload Multimedia and Upload Documents. New: a "+" menu only inside a project, offering share files, invite a family member, and for admins create an event, add a family, add units and invite people. It has no new project and no separate "new admin" (admins are invited). (`2c8e8b13:resources/js/components/QuickActions.vue`; `resources/js/components/layout/QuickActions.tsx`)
- **The assistant is not built.** It is planned (refactor item 14), but no code was found (checked with grep in `app`, `resources/js`, `config`, `routes`).

## Other: who may do what (authorization overview)

### No longer possible / newly restricted

- **Each admin action checks the right project.** Before, the policies checked mostly the role: `isAdmin()` for creating, updating and deleting families, units, unit types, the plan and projects. Project isolation came from global scopes (`ProjectScope` on User, Family, Unit, UnitType, Plan, Log; Project's `available` scope), `ProjectScopedRequest` and the `BelongsToProject` rule. Now every check names the project: `manages($project)` for changes and `canAccess($project)` for reading. (`2c8e8b13:app/Policies/*.php`; `2c8e8b13:app/Models/Concerns/ProjectScope.php`; `app/Policies/ProjectResourcePolicy.php`; `app/Models/User.php`)
- **A record can no longer be moved to another project through an edit.** Before, `UpdateFamilyRequest` and `UpdateUnitRequest` required and validated a `project_id`, and the controllers passed `validated()` into `update()`. A family's or unit's `project_id` could be changed to any existing project; for families this included the family's own members, through `FamilyPolicy::update`. Now records come only through the project in the URL (scoped bindings), and the requests accept no `project_id`. This is verified in code only; no old test covers it. (`2c8e8b13:app/Http/Requests/UpdateFamilyRequest.php`; `2c8e8b13:app/Http/Requests/UpdateUnitRequest.php`; `2c8e8b13:app/Http/Controllers/Resources/FamilyController.php` update; `routes/web.php` scopeBindings; `tests/UseCases/Membership/ProjectIsolationTest.php` "does not reach another project's records…")
- **Members cannot reach admin tools by URL.** All admin tools sit under `/projects/{p}/manage` behind `can:update,project`, which needs an admin membership or superadmin. Members get 403, outsiders 404. (`routes/web.php`; `tests/Browser/Features/ProjectsTest.php` "keeps members out of the admin area")

### Newly possible / newly permitted

- **New project-level abilities.** `ProjectPolicy` adds `invite`, `removeMember`, `rankUnits` (anyone with a family, admins included), `runLottery`, `invalidateLottery` (admins), `requestApproval` and `approve` (superadmins only). (`app/Policies/ProjectPolicy.php`)
- **Rights by role** (old → new):
  - **Guest:** none → can sign up and start a project. Account flows are covered in another section.
  - **Member:**
    - can now leave a project, belong to several projects, and see their own project's home;
    - can no longer edit their own family, delete their own account, or see other people's private email and phone.
  - **Family member who is also an admin:** never existed before → can now rank units for their family.
  - **Project admin:**
    - can now promote, demote and remove other admins, move people between families, invite existing accounts, delete or archive their project, and ask for approval;
    - can no longer edit people's names and emails, assign several projects in one invitation, or see legal IDs.
  - **Superadmin:**
    - can now approve, restore and delete projects for good in the console, and sees what each permanent delete removes;
    - no longer gets RSVP from the bypass.

  (paths in the sections above)

### Works differently

- **Superadmin bypass.** Before: `Gate::before` gave superadmins every ability, and global scopes were skipped for them. Now: `Gate::before` grants everything except `rankUnits`, `rsvp` and `deploy`. Superadmins reach every project through `canAccess` and `manages` without a membership. (`2c8e8b13:app/Providers/AppServiceProvider.php`; `app/Providers/AppServiceProvider.php`)
- **Where each check lives.** Before, authorization was spread over `authorizeResource` in a base controller, FormRequest `authorize()`, global scopes that silently filtered queries (including a call-stack inspection to skip scoping while loading the signed-in user), and `Gate::denyIf` in controllers. Now there are explicit policies, `ResolveProject` middleware and route-group `can:` middleware; models have no global scopes. Rules for reading are unchanged: everyone in a project sees its families, people, units, unit types and plan. (`2c8e8b13:app/Http/Controllers/Resources/Controller.php`; `2c8e8b13:app/Models/Concerns/ProjectScope.php` isCalledByLaravelAuthSystem; `app/Http/Middleware/ResolveProject.php`; `app/Policies/ProjectResourcePolicy.php`)
- **Old rules not written down before.** `app/Policies/README.md` claimed checks the old code did not make: `FamilyPolicy::view` "must be in same project" and `UnitPolicy::update` "admin managing unit's project". The code returned `true` or `isAdmin()`, and the scopes did the isolation. (`2c8e8b13:app/Policies/README.md`; `2c8e8b13:app/Policies/FamilyPolicy.php`; `2c8e8b13:app/Policies/UnitPolicy.php`)

## Other: miscellaneous

### No longer possible / newly restricted

- **The activity log page is gone.** Old: `/logs` and `/logs/{id}` were open to every signed-in user, limited to their own projects. No menu linked to them, and the `logs` table was filled only by the seeder: no code wrote real entries. New: no log table, page or model. Lottery runs keep their own audit (lottery section). Recording events for superadmins to review is planned (refactor item 18). (`2c8e8b13:routes/web/users.php`, `2c8e8b13:app/Http/Controllers/Resources/LogController.php`, `2c8e8b13:app/Policies/LogPolicy.php`, `2c8e8b13:app/Models/Log.php`, `2c8e8b13:database/seeders/LogSeeder.php`, `2c8e8b13:tests/Feature/Healthcheck/LogsHealthcheckTest.php`; new `routes/web.php`, `database/migrations/`)
- **The development pages are gone.** Old: `/dev/*` (dashboard, UI, filters, forms, cards, entity cards, plans, playground, flash tester), in local and testing only, linked from the sidebar in local. New: none. A UI Lab sits in the platform console for superadmins, in every environment. (`2c8e8b13:routes/web/dev.php`, `2c8e8b13:resources/js/components/layout/NavFooter.vue`; `routes/web.php` admin.lab)
- **The API is gone.** Old: `routes/api.php` called `Route::devApi()` from the local `laravel-instant-api` package. Unverified: which endpoints that generated, because the package was a submodule not in the tree. Sanctum was installed with a `personal_access_tokens` table, but `User` had no `HasApiTokens`, so no token could be issued. New: no API routes, and neither Sanctum nor the package is installed. (`2c8e8b13:routes/api.php`, `2c8e8b13:composer.json`, `2c8e8b13:.gitmodules`, `2c8e8b13:database/migrations/2025_08_10_073958_create_personal_access_tokens_table.php`; `bootstrap/app.php`, `composer.json`)
- **No project chosen by cookie.** Old: the project was the "current project" cookie (`projects/current/{project}`). New: the project is in the URL, so links can be shared and each tab can show a different project. (`2c8e8b13:routes/web/context.php`; `routes/web.php`)

### Newly possible / newly permitted

- **An institution line under the brand, set per deployment** (`APP_INSTITUTION`). (`config/app.php`, `app/Http/Middleware/HandleInertiaRequests.php`)
- **Staging and dev skip mail to blocked domains** (by default the demo accounts' domains). (`config/mail.php`, `app/Listeners/SkipBlockedRecipients.php`, `tests/UseCases/Mail/BlockedDomainsTest.php`)

### Works differently

- **Health checks are unchanged.** `/ping` and `/up` are the same in both. (`2c8e8b13:routes/web.php`, `bootstrap/app.php`; `routes/web.php`)
