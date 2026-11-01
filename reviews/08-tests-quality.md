# Test suite & overall quality gates
_Review of main · scope: tests/ (Arch, Unit, UseCases, Browser, Stress, Helpers, Concerns), phpunit.xml, `./mtav pest` wrapper, .github/workflows/pipeline.yml (test jobs), eslint.config.js, config/insights.php, .husky hooks_

## Summary
For an app this size, the suite is wide and well organised. Use-case tests go through HTTP and are built with explicit scenario builders (`tests/Helpers/scenario.php`). `toBeDenied()` is a strict custom expectation. Cross-project GET isolation, contact privacy, uploads (pixel bombs, SVG, size), blocking and deletion, and the lottery's max-min outcomes all have tests. CI runs Pint, ESLint, Prettier, tsc, Pest (Arch/Unit/UseCases), Vitest and a full Chromium suite on every push. The biggest risk sits in the lottery, and the tests cannot see it. The random tie-breaker the docs promise never reaches GLPK: it is rounded away when the data file is written, and it is a constant per family, so it would cancel out anyway. Families with identical rankings are therefore decided by GLPK's fixed solving order. The tests assert only integer ranks, and their comments describe decimals that are never emitted. Other confirmed problems:
- The test meant to protect other superadmins targets the acting superadmin twice.
- No test sweeps the route table for role denial, and several destructive endpoints (removing a member, deleting forum posts, revoking superadmin) have no denial or business-rule tests.
- Upload type checks are tested only with fakes that report whatever type you declare.
- Smaller issues: fixed sleeps in browser tests, an opt-in Stress suite, a cleanup test that cannot fail, narrow axe coverage, PR runs that report skipped jobs, and no PHP static analysis.

## Findings

| # | Severity | Category | Title | Location |
|---|---|---|---|---|
| 08-01 | High | correctness | Lottery tie-breaker is rounded away before GLPK sees it, cannot break ties anyway, and tests only check integer ranks | `app/Services/Lottery/Solvers/Glpk/DataGenerator.php:117-128` |
| 08-02 | Medium | tests | "protects superadmins and oneself" never targets another superadmin | `tests/UseCases/Platform/AccountsTest.php:94-104` |
| 08-03 | Medium | security | No route sweep for role denial; several privilege-sensitive endpoints have no "denied" test | `routes/web.php:217-264,270-299` |
| 08-04 | Medium | tests | Business rules in destructive endpoints are untested (removing the last admin, forum post deletion, revoking superadmin) | `app/Http/Controllers/Manage/PeopleController.php:51-67` |
| 08-05 | Medium | security | Upload type checks only exercised with fakes that report the declared type | `tests/UseCases/Community/MediaTest.php:47-106` |
| 08-06 | Low | tests | Browser ranking tests sleep a fixed 2s, then assert once on the DB after a 600ms debounced save | `tests/Browser/Features/LotteryTest.php:39,52,77,101,219` |
| 08-07 | Low | tests | Stress suite (binary-search vs direct equivalence on hard instances) never runs in CI | `mtav:302`, `.github/workflows/pipeline.yml:79-80` |
| 08-08 | Low | tests | "cleans up temp files even when execution fails" watches a pattern never created, so it cannot fail | `tests/Unit/Lottery/Glpk/FailureHandlingTest.php:47-73` |
| 08-09 | Low | accessibility | axe runs on about 11 page loads and the UI Lab only; never with a dialog open; no JSX a11y lint | `tests/Browser/Features/AccessibilityTest.php:7-30` |
| 08-10 | Low | practices | Same-repo pull requests report checks/tests/browser as "skipped", which GitHub treats as passing | `.github/workflows/pipeline.yml:33,72,96` |
| 08-11 | Low | practices | Dead, broken test trait in tests/Concerns; no PHP static analysis; phpinsights cannot fail | `tests/Concerns/Http.php:5-6,103`, `config/insights.php:163-169` |

### 08-01. Lottery tie-breaker is rounded away before GLPK sees it, cannot break ties anyway, and tests only check integer ranks
- **Severity / category:** High / correctness
- **Location:** `app/Services/Lottery/Solvers/Glpk/DataGenerator.php:117`, `:122`, `:128`; `app/Services/Lottery/Solvers/Glpk/ModelGenerator.php:36,74,78`; `tests/Unit/Lottery/Glpk/DataGeneratorTest.php:79-101,137-152`; `documentation/technical/lottery.md:47`
- **Problem:** `buildPreferenceMatrix` draws `$tieBreaker = mt_rand(0, 10000) / 100000000.0`, so at most 0.0001. It adds this to each rank and writes the value with `sprintf('%.3f', …)`, so every value is printed as an exact integer (e.g. 1.0001 becomes `1.000`). The docblock (lines 96-100) claims "1.XXX, 2.YYY" with three random decimals, which is not what the code emits.
  - Even if the term survived, it is drawn once per family and added to all of that family's units. Each family takes exactly one unit (`= 1` constraint), so the term adds the same constant to every feasible assignment in the Phase 2 objective (`ModelGenerator.php:74`) and can never change which solution is optimal.
  - The only other randomness in `app/Services/Lottery` is in `RandomSolver`.
  - Ties are therefore settled by GLPK's deterministic search over a data file that lists families in a fixed order.
  - `DataGeneratorTest` casts the values to `(int)`. Its comments say "Tie-breaker decimals (0.000-0.999) are added", so it can neither detect the rounding nor pin any tie-breaking behaviour. No test checks that symmetric families can get different outcomes.
- **Failure scenario:** Two families of one type both rank units A then B. One appears first in the `.dat` file. The max-min and sum objectives are tied, so GLPK returns the same optimal vertex every time. The same family always gets A. An admin who invalidates and re-runs gets the identical result, which contradicts `lottery.md:47` ("the lottery is a lottery only where the rankings leave it open"). The same applies to orphan selection when there are more families than units.
- **Recommended fix:** Keep integer ranks `r[c,v]` for the Phase 1 and Phase 2 constraints (`z >= …`, `… <= S`). Add a separate objective-only param `p[c,v] = r[c,v] + e[c,v]`, where `e` varies per (family, unit) and its total across all families stays below 1, so it never overturns an integer optimum. Draw it with `random_int` (or from a per-run seed recorded in the audit). Write it with enough precision (`%.9f` or scaled integers). Then add tests:
  - parse the `.dat` file and assert non-integer, per-unit-distinct objective weights;
  - run two families with identical rankings over two units about 30 times through `GlpkSolver` and assert both outcomes occur;
  - keep the existing exact-pick tests (they have unique optima).
- **Verification note:** Read `DataGenerator.php:107-135` and both GMPL models. Confirmed the 1e-4 bound, the `%.3f` format and the per-family constant. Grepped `app/Services/Lottery` for any other RNG (`shuffle` appears only in `RandomSolver`).

### 08-02. "protects superadmins and oneself" never targets another superadmin
- **Severity / category:** Medium / tests
- **Location:** `tests/UseCases/Platform/AccountsTest.php:13,94-104`; `tests/Helpers/scenario.php:23-26`; `app/Http/Controllers/Platform/UserController.php:66-71`
- **Problem:** `aSuperadmin()` returns `User::firstWhere('is_superadmin', true)` when one exists. `beforeEach` already set `$this->superadmin = aSuperadmin()`, and `aProject()` calls it as well, so `$other = aSuperadmin()` is the same account as the actor. Both `$act($other)` and `$act($this->superadmin)` hit only the `$user->is($request->user())` branch of `ensureNotProtected`. The `|| $user->isSuperadmin()` branch is never run. No other test blocks or deletes a second superadmin (grepped `superadmin()->create`, `admin.users.block`, `admin.users.destroy`).
- **Failure scenario:** Someone drops `|| $user->isSuperadmin()` from `UserController.php:68`. Any superadmin can then block or delete every other superadmin, including the owner, and the whole suite stays green.
- **Recommended fix:** Create the second superadmin explicitly (`User::factory()->superadmin()->create()`) and assert it differs from the actor. Rename the helper `theSuperadmin()`, or add `anotherSuperadmin()`, so the reuse is visible at call sites.
- **Verification note:** Traced `aSuperadmin()` and the `beforeEach` order. `$other` and `$this->superadmin` resolve to the same row.

### 08-03. No route sweep for role denial; several privilege-sensitive endpoints have no "denied" test
- **Severity / category:** Medium / security
- **Location:** `routes/web.php:217` (`can:update,project` on `/manage`), `:233-234`, `:262`, `:270` (`EnsureSuperadmin`), `:280`, `:286`; `routes/channels.php:19`; `tests/UseCases/Membership/ProjectIsolationTest.php:27-53`; `tests/UseCases/Platform/ErrorLogTest.php:51-53`
- **Problem:** Authorization for whole areas rests on group middleware. Tests check denial route by route, and these routes have none:
  - `manage.people.update`: no test that a member cannot PUT `is_admin=true` for themselves.
  - `manage.people.destroy`: no denial test.
  - `manage.lottery.invalidate`: tested only as the project's own admin (`ExecutionTest.php:151-184`); no member and no other-project admin.
  - `admin.users.superadmin`: never called in UseCases.
  - `admin.errors.destroy`: only `errors.index` is denied.
  - `superadmins` broadcast channel: `LiveUpdatesTest` checks `projects.{id}` and `users.{id}` only.
  - `ProjectIsolationTest` covers seven GET pages; it has no writes.
  - No Arch test walks `Route::getRoutes()` (`tests/Arch` holds only `LanguagesTest.php` and `SmellsTest.php`).
- **Failure scenario:** A developer moves `Route::put('people/{member}', …)` up into the member group, or adds a new admin-only route outside the `manage` group. Any family member can then `PUT /projects/{p}/manage/people/{self}` with `is_admin=1` and become an admin (the controller itself does no authorization, `PeopleController.php:23-49`). Likewise, a member can `POST …/lottery/runs/{run}/invalidation` and wipe every assignment. No test fails.
- **Recommended fix:** Add one dataset-driven test that enumerates named routes with the `manage.` and `admin.` prefixes, fills route parameters from a scenario, and asserts `toBeDenied()` for:
  - a member of the project;
  - an admin of another project;
  - a non-superadmin, for `admin.*`.

  Keep an explicit allow-list for exceptions. Also add explicit tests for member self-promotion through `manage.people.update`, a member invalidating a run, and the `superadmins` channel closure.
- **Verification note:** Grepped `tests/` for each route name. `people.destroy`, `users.superadmin` and `posts.destroy` have zero UseCases references, and `lottery.invalidate` and `errors.destroy` have no denial assertions. Today the middleware does protect all of them; this is a regression gap, not a live hole.

### 08-04. Business rules in destructive endpoints are untested (removing the last admin, forum post deletion, revoking superadmin)
- **Severity / category:** Medium / tests
- **Location:** `app/Http/Controllers/Manage/PeopleController.php:51-67`; `app/Http/Controllers/Member/ForumController.php:113-143`; `app/Policies/PostPolicy.php:16-19`; `app/Http/Controllers/Platform/UserController.php:97-108`
- **Problem:** Three sets of rules are not covered by UseCases tests:
  - **Removing a member:** `ensureNotLastAdmin` on removal (line 53) is never run. `tests/Browser/Features/PeopleTest.php:177-188` covers removing a plain member, and `:170-174` covers last-admin demotion through `update`, but nothing removes the only admin.
  - **Deleting forum posts:** `forum.posts.destroy` is never called, so these branches are uncovered: an author deleting a reply, an admin deleting a reply, a neighbour denied, and an opening post routed to `destroy()`, which must 403 for its author once `replies_count > 0`.
  - **Revoking superadmin:** `admin.users.superadmin` has a browser happy path only (`tests/Browser/Features/PlatformTest.php:15`). The `cannot_revoke_self` guard (line 101) and denial for non-superadmins are untested.
- **Failure scenario:** A change to `PostPolicy::delete`, such as `||` turning into a looser check, lets any member delete a neighbour's reply. Or the `cannot_revoke_self` check is dropped and the only superadmin demotes themselves, locking everyone out of `/admin`. Or the last-admin check on removal is broken, and an admin removes the project's only admin, leaving it unmanageable. Each of these passes CI.
- **Recommended fix:** Add UseCases tests:
  - People: an admin removes the only admin and gets the `member` error, with the membership kept.
  - Forum: the author and an admin delete a reply; a neighbour is denied; the author of an opening post with replies gets 403.
  - Superadmins: revoke another account; revoking self is refused; a non-superadmin is denied.
- **Verification note:** Grepped tests for these routes and guard keys; confirmed the browser coverage noted above, so "no test at all" for removing a member was corrected.

### 08-05. Upload type checks only exercised with fakes that report the declared type
- **Severity / category:** Medium / security
- **Location:** `tests/UseCases/Community/MediaTest.php:47-59,91-106`; `tests/UseCases/Accounts/AvatarTest.php:24-32`; `app/Http/Requests/MediaRequest.php:14,34,40`; `app/Services/MediaService.php:124-125`
- **Problem:** Every server-side upload test uses `UploadedFile::fake()`. For `create($name, $kb, $mime)`, its `getMimeType()` returns the declared mime. For `createWithContent()` and `image()`, it returns a guess from the file name; it never sniffs the bytes. The tests therefore show:
  - the SVG test (line 98) is rejected because of the `.svg` name;
  - the `.exe` test is rejected because of its declared mime;
  - the PDF, m4a and avatar-PDF tests pass because of their declared mime.

  Production relies on Symfony's finfo-based `getMimeType()`/`guessExtension()` for the `mimetypes:` rule, the stored `mime_type` and the stored extension. That is the content-checking guarantee that `MediaRequest.php:14` states, and no test supplies disguised content.
- **Failure scenario:** A refactor switches `MediaService::storeOne` to `getClientMimeType()`/`getClientOriginalExtension()`, or switches validation to rules based on the client's claimed type. Every test stays green. In production, a `photo.jpg` whose bytes are SVG with `<script>` is then accepted and stored with an image type.
- **Recommended fix:** Build real uploads from temp files, e.g. `new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true)` with SVG or HTML bytes, and an `MZ` PE header named `minutes.pdf`. Assert they are rejected and nothing lands on the Media disk. Add a positive case asserting that `mime_type` and the extension come from the content.
- **Verification note:** Read every upload test and `aPixelBomb()` (also `createWithContent`). No test in `tests/` constructs a real `UploadedFile`.

### 08-06. Browser ranking tests sleep a fixed 2s, then assert once on the DB after a 600ms debounced save
- **Severity / category:** Low / tests
- **Location:** `tests/Browser/Features/LotteryTest.php:39,52,77,101,219`; `tests/Browser/Critical/CooperativeJourneyTest.php:105-107`; `resources/js/pages/Member/Lottery.tsx:89-101`
- **Problem:** Ranking changes go through `useDebouncedCallback(…, 600)` and then `router.put`. The tests click, `->wait(2)`, and then either run a plain `expect()` on `PreferencesService`/DB, which runs once with no retry, or navigate away with `visit()`. Navigating away also discards a debounced save that has not fired yet. The 1.4s margin usually holds, but nothing synchronises on the request finishing. The suite has 16 fixed `wait()` calls in all.
- **Failure scenario:** On a loaded runner, the PUT completes about 2.1s after the click. `expect($garcia->preferences()->first()?->id)->toBe($second->id)` reads the old ranking, and the Critical journey fails intermittently. Re-runs pass, which erodes trust in the gate that guards image publishing.
- **Recommended fix:** Poll the DB expectation (retry every 100ms for up to 10s). Or expose a settled state from `onFinish` (e.g. `data-saved`) and assert it with Pest's retrying assertions; this also removes about 12s of fixed sleep.
- **Verification note:** Read the cited waits and the debounce. Severity lowered from medium: the margin is real, and no failure was observed.

### 08-07. Stress suite (binary-search vs direct equivalence on hard instances) never runs in CI
- **Severity / category:** Low / tests
- **Location:** `mtav:302`; `.github/workflows/pipeline.yml:79-80`; `tests/Stress/Lottery/Glpk/BinarySearchEquivalenceTest.php:22-42`; `tests/Stress/Lottery/Glpk/GlpkTimeoutTest.php:56-76`; `config/lottery.php:74`; `app/Services/Lottery/Solvers/Glpk/Glpk.php:41-44`
- **Problem:** `./mtav pest` defaults to `--testsuite Arch,Unit,UseCases`, and CI calls it with no arguments. Stress runs only when someone remembers (`tests/Stress/README.md`: "Run them after touching anything in Solvers").
  - `glpk_phase1_max_size` defaults to 0, so `familyCount() >= 0` always picks `HybridDistribution`: binary search is the only production path.
  - Unit tests exercise it on tiny instances: `HybridDistributionTest.php:110-124` asserts S=2 on one case.
  - Equivalence with direct Phase 1 on the degenerate instances that motivated it is checked only in Stress.
  - The original finding's "INFEASIBLE vs OPTIMAL-with-zeros" scenario is already caught in CI by `ModelGeneratorTest.php:62,69`, which pins the `= 1` constraints, and by `SolutionParserTest.php:212`, which throws on all-zero OPTIMAL. That part was dropped.
- **Failure scenario:** A change to the binary search's bounds or its feasibility check returns a non-minimal S only on larger or degenerate instances. Families then get worse worst-case ranks than the optimum, and CI stays green.
- **Recommended fix:** Move a reduced subset of the equivalence dataset that finishes in seconds into `tests/Unit/Lottery/Glpk`. Add a scheduled or path-filtered CI job (changes under `app/Services/Lottery/Solvers/**`) that runs `./mtav pest --testsuite Stress`.
- **Verification note:** Read the wrapper default, the pipeline, the Stress tests and the unit-test mitigations. Severity lowered from medium, and the failure scenario was replaced.

### 08-08. "cleans up temp files even when execution fails" watches a pattern never created, so it cannot fail
- **Severity / category:** Low / tests
- **Location:** `tests/Unit/Lottery/Glpk/FailureHandlingTest.php:47-73`; `app/Services/Lottery/Solvers/Glpk/lib/Files.php:42,55,99,109`; `app/Services/Lottery/Solvers/Glpk/TaskRunners/TaskRunner.php:83-84`
- **Problem:** The test compares `glob(sys_get_temp_dir().'/mtav_*')` before and after a failed run. The model and data files use the prefixes `phase1_`, `data_`, `phase2_` and `data_s_` (`Files::write` via `tempnam($dir, $prefix)`). The only `mtav_` name, the `mtav_sol_` reservation, is unlinked straight away (line 109) and would only be recreated by glpsol, which here is `/nonexistent/glpsol`. The before and after counts are equal whatever cleanup does.
- **Failure scenario:** Someone deletes the `finally { $this->files->cleanup(...) }` in `TaskRunner.php:83-84`. Every failed or timed-out lottery then leaks `.mod`/`.dat` files holding every family's ranking into `/tmp`, and this test still passes.
- **Recommended fix:** Set `temp_dir` to a fresh empty directory created for the test and assert it is empty afterwards. Or glob the real prefixes.
- **Verification note:** Read `Files::write`/`reserveSolutionPath` and the test. No `mtav_*` file can survive or be created.

### 08-09. axe runs on about 11 page loads and the UI Lab only; never with a dialog open; no JSX a11y lint
- **Severity / category:** Low / accessibility
- **Location:** `tests/Browser/Features/AccessibilityTest.php:7-30`; `tests/Helpers/browser.php:145-155`; `eslint.config.js:6-20`
- **Problem:** `assertAccessible` runs on login, register, five member pages, four admin pages and the UI Lab. It is called nowhere else in `tests/Browser`, and the Lab sections (`resources/js/lab`) contain no modal or dialog. Mantine dialogs are never checked while open, yet they carry most create and edit flows (hence the `inDialog()` helpers). Chat, forum, people, notifications, profile, invitation acceptance and password reset are never checked. ESLint has no `jsx-a11y` rules.
- **Failure scenario:** The invite dialog ships an icon-only remove button without an `aria-label`, or a `TextInput` without a label. That is a serious `button-name` or `label` violation, screen-reader users cannot operate the dialog, and CI stays green.
- **Recommended fix:** Call `assertAccessible` after opening the main dialogs in existing feature tests (invite, add family, add unit, share files, run-lottery confirmation). Add the missing pages to the route lists. Add `eslint-plugin-jsx-a11y` (flat recommended config).
- **Verification note:** Grepped for `assertAccessible` outside `AccessibilityTest.php` and for Modal/Dialog in `resources/js/lab`: none.

### 08-10. Same-repo pull requests report checks/tests/browser as "skipped", which GitHub treats as passing
- **Severity / category:** Low / practices (plausible; depends on branch-protection settings not visible in the repo)
- **Location:** `.github/workflows/pipeline.yml:13,33,72,96,113`
- **Problem:** `checks`, `tests` and `browser` run only when `github.event_name != 'pull_request'` or the PR comes from a fork. For a same-repo PR, the `pull_request` run finishes with all three, and `image`, skipped. GitHub reports a job skipped by `if:` as successful for required status checks. Because the concurrency group differs (`refs/pull/N/merge`), this run sits beside the push run on the same SHA instead of replacing it.
- **Failure scenario:** A branch's push run fails "Tests", and the author then opens a PR. The newer PR run posts "Tests" and "Browser suite" as skipped. If those are required checks, the PR can show as mergeable despite the red push run.
- **Recommended fix:** Either remove the `pull_request` trigger for same-repo PRs (gate the whole workflow on fork origin with a guard job), or run the jobs on PRs and drop the duplicate push run with a concurrency key based on the SHA. Document which event's checks branch protection requires.
- **Verification note:** Read the job conditions and concurrency key. The repository's branch-protection configuration could not be inspected.

### 08-11. Dead, broken test trait in tests/Concerns; no PHP static analysis; phpinsights cannot fail
- **Severity / category:** Low / practices
- **Location:** `tests/Concerns/Http.php:5-6,95-104`; `tests/Concerns/Utilities.php:5-8`; `.github/workflows/pipeline.yml:59-69`; `.husky/pre-push:1-2`; `config/insights.php:163-169`
- **Problem:** `Tests\Concerns\Http` imports `App\Models\Admin` and `App\Models\Member` (neither exists in `app/Models`) and calls `model()`, `isNotAdmin()` and `isNotMember()`, none of which is defined anywhere. No test or `Pest.php` uses the trait, and it survives only because nothing type-checks PHP:
  - CI's PHP step is `pint --test` (style only);
  - there is no PHPStan or Larastan in `composer.json` and no `phpstan.neon`;
  - the pre-push hook runs `artisan insights` with every `requirements` threshold commented out, so it never fails, and it needs the dev container running.
- **Failure scenario:** A contributor uses the documented-looking `visitRoute(asAdmin: …)` and gets a fatal "Class App\Models\Admin not found". More broadly, an undefined-method or nullable-dereference error in `app/` (e.g. calling a method on a null `membershipIn()` result) passes every gate unless a test happens to execute that line.
- **Recommended fix:** Delete `tests/Concerns`. Add Larastan to the `checks` job, starting at a modest level with a baseline. Either set insights requirements or drop the pre-push hook so it does not look like a gate.
- **Verification note:** Grepped for trait usage, `function model`, `isNotAdmin`, phpstan/larastan; listed `app/Models`. All confirmed absent.

## Dropped during verification
- **`invitationLink()` assigns `$url` before checking the recipient** (`tests/UseCases/Membership/InvitationsTest.php:39-50`). The code is as described, but every current caller sends exactly one invitation first, so no test is affected today. This is a latent helper defect with no concrete failure, and was dropped as speculative. The browser helper `invitationLinkFor()` already does it correctly.

## Checked, nothing to report
- `toBeDenied()`/`toBeNotFound()` custom expectations (`tests/Helpers/expectations.php:82-92`): strict (403/404 or the explicit not-allowed flash), not vacuous.
- Skipped or todo tests: none found (`->skip(`, `->todo`, `markTestSkipped`).
- phpunit.xml environment: isolated (array cache and session, sync queue, null broadcast, low bcrypt rounds); the suites are declared consistently with the wrapper.
- Cross-project GET isolation and scoped bindings for nested records: covered (`ProjectIsolationTest.php:27-53`).
- Lottery outcome tests (exact picks, max-min, more units or families, invalidation and re-run): present and asserting real outcomes, apart from tie-breaking (08-01).
- Frontend gates: ESLint (TS and react-hooks), Prettier and tsc run in CI (`pipeline.yml:62-69`).
