# MTAV — Code Quality Review

**Date:** 2026-05-20
**Reviewer:** Claude (Opus 4.7), commissioned code-quality pass
**Status:** 🟡 DRAFT — awaiting author review & approval. No code has been changed.

---

## 1. Scope & Method

This review was a read-the-code pass, not a guess-from-names pass. Every finding below
was confirmed against the actual source; where a conclusion could not be fully verified
without running the code, it is explicitly marked **(verify)**.

**Reviewed in depth:**

- The Lottery domain — `app/Services/Lottery/**` (orchestration, solvers, GLPK task
  runners, data objects, process/file handling).
- All Eloquent models — `app/Models/**` including the `Concerns` traits.
- HTTP layer — controllers, FormRequests, policies, middleware, routes.
- `app/helpers.php`, `config/lottery.php`, `bootstrap/app.php`.
- Test infrastructure — `tests/Pest.php`, `tests/TestCase*.php`, `tests/Concerns/**`,
  and a representative sample of Unit/Feature/Healthcheck tests.

**Lighter pass (structural only, not line-by-line):**

- The Vue/Inertia frontend — `resources/js/**` (324 `.vue` + 112 `.ts` files; too large
  for a line-by-line audit in a single pass).
- The two local Composer packages — `packages/laravel-instant-api`,
  `packages/laravel-resource-tools`.

A second pass would be needed to give the frontend and the local packages the same
depth as the backend.

---

## 2. How to read this document

Each finding has a stable ID (`H1`, `M3`, …). Severity:

| Badge | Meaning |
|-------|---------|
| 🔴 High | Production-affecting: a real bug, a security weakness, or temporary code shipped live. |
| 🟠 Medium | Should be fixed: latent bug, dead code, fragility, or a doc/code contradiction. |
| 🟡 Low | Polish: minor inconsistency, noise, or style. |

Each finding lists **Effort** as a rough guide: *trivial* (minutes), *small* (<1h),
*medium* (a few hours), *large* (a refactor).

Nothing in this document has been applied. Section 8 proposes a sequencing once approved.

---

## 3. Summary table

| ID | Sev | Title | Effort |
|----|-----|-------|--------|
| H1 | 🔴 | Temporary `BroadcastNavigationTest` middleware is live in production | ✅ done |
| H2 | 🔴 | `ExecutionService::applyResults()` applies lottery results without a transaction | trivial |
| H3 | 🔴 | CSRF protection disabled on `login` and `logout` | small |
| H4 | 🔴 | `enumFromValue()` operator-precedence bug | trivial |
| H5 | 🔴 | `models()` helper is broken for iterables (`instanceof iterable`) | trivial |
| H6 | 🔴 | GLPK timeout: float config (`0.5`) silently truncated to `int 0` | small |
| H7 | 🔴 | `glpk_phase1_max_size` default `0` disables the entire GLPK phase-1 path | small |
| M1 | 🟠 | ~75 files carry an unresolved `// Copilot - Pending review` marker | medium |
| M2 | 🟠 | Dead code in `LotteryOrchestrator::reportException()` | trivial |
| M3 | 🟠 | Dead `@deprecated` method `LotterySpec::sanitizeUnitIdArray()` | trivial |
| M4 | 🟠 | Raw interpolated `DELETE` in `PreferencesService::sanitizeBeforeFetch()` | small |
| M5 | 🟠 | `app/Policies/README.md` contradicts the actual policy code | small |
| M6 | 🟠 | Temp-file TOCTOU window in `Files::write()` / `reserveSolutionPath()` | small |
| M7 | 🟠 | Undefined variable `$unitType` in `UnitController::store()` | trivial |
| M8 | 🟠 | Inconsistent null-safety around `asMember()->family` / `$family->unitType` | small |
| M9 | 🟠 | `FamilyController::destroy()/restore()` cascade is not transactional | trivial |
| M10 | 🟠 | GLPK Phase-1 objective: fractional `p` vs integer `z` interaction (verify) | medium |
| M11 | 🟠 | Stress tests run as part of the default `pest` suite | trivial |
| M12 | 🟠 | `encryptCookies(except: ['project'])` names a cookie that doesn't exist | trivial |
| L1–L10 | 🟡 | Assorted polish items (see §6) | trivial each |

Test-suite assessment is in §7.

---

## 4. High-priority findings

### H1 🔴 — Temporary test middleware is live in production ✅ **RESOLVED 2026-05-20**

**Resolution.** Middleware deleted, registration removed from `bootstrap/app.php`, the
now-orphan `BroadcastMessage::USER_NAVIGATION` enum case removed, and the matching
`user.navigation` entries stripped from `useBroadcasting.ts`, `broadcasting.d.ts`, and
the leftover scaffolding in `AppSidebarHeader.vue`. Both `BROADCASTING.md` docs updated
to remove the now-obsolete "Temporary Test" sections; the manual testing instructions
now use `BroadcastService` from `tinker`.

**Location:** `app/Http/Middleware/BroadcastNavigationTest.php`, registered at
`bootstrap/app.php:43`.

**Problem.** The middleware's own header reads:

```php
// TEMPORARY TEST MIDDLEWARE - Remove after testing
/**
 * Test middleware to broadcast user navigation events.
 * This is TEMPORARY and should be removed after testing.
 */
```

It is appended to the global `web` middleware group:

```php
// bootstrap/app.php:38-44
$middleware->web(append: [
    HandleInvitedUsers::class,
    HandleSelectedProject::class,
    AddLinkHeadersForPreloadedAssets::class,
    HandleInertiaRequests::class,
    BroadcastNavigationTest::class, // TEMPORARY TEST - Remove after testing
]);
```

So on **every web request**, for every authenticated user, the app broadcasts the user's
URL, path, HTTP method, id and name to a private channel and (when resolvable) a project
channel.

**Secondary bug inside it.** Line 53 reads `session('current_project_id')`, but the app
never stores the project there — the current project lives in the `projectId` **cookie**
(`app/helpers.php:61`, `selectProject()`). That fallback branch is therefore dead; only
`$request->route('project')?->id` ever yields a project id.

**Impact.**

- A broadcast (Reverb / queue work) on every single page load — measurable overhead.
- Every user's complete navigation trail is broadcast on channels — a privacy concern.
- It is unreviewed (`// Copilot - Pending review`) and self-described as throwaway.

**Recommended fix.** Delete the middleware class and its registration. **Before deleting**,
grep the frontend for a listener on the `USER_NAVIGATION` broadcast message — if a real
feature depends on it, it must be reworked into a proper, intentional feature rather than
"test" middleware.

**Effort:** small.

---

### H2 🔴 — Lottery results are applied without a transaction

**Location:** `app/Services/Lottery/ExecutionService.php:76-83`.

**Problem.**

```php
public function applyResults(int $lotteryId, array $picks): void
{
    foreach ($picks as $familyId => $unitId) {
        Unit::where('id', $unitId)->update(['family_id' => $familyId]);
    }

    Event::whereId($lotteryId)->delete(); // soft-delete
}
```

Each pick is a separate `UPDATE`, and the whole sequence — N updates plus the Event
soft-delete — is **not wrapped in a transaction**. If anything fails partway (DB error,
timeout, deadlock), the lottery is left **partially applied**: some families assigned,
some not, with no clean rollback.

This is the system's core operation — the moment a housing lottery's outcome is committed.
Note that `invalidate()`, directly below it (`ExecutionService.php:93-108`), *is* correctly
wrapped in `DB::transaction()` — so the inconsistency is also internal.

**Recommended fix.** Wrap the loop and the delete in `DB::transaction()`. Optionally
collapse the N updates into a single statement (a `CASE` expression or per-unit-type
batched update) for performance, but the transaction is the essential fix.

**Effort:** trivial.

---

### H3 🔴 — CSRF protection disabled on `login` and `logout`

**Location:** `bootstrap/app.php:49-53`.

**Problem.**

```php
$middleware->validateCsrfTokens(except: [
    'login',
    'logout',
    'csrf-token',
]);
```

Excluding `login` from CSRF enables a **login CSRF** attack: an attacker can force a
victim's browser to silently log into an *attacker-controlled* account, after which the
victim's actions happen inside the attacker's session. Excluding `logout` allows
forced-logout (a nuisance, lower severity).

This looks like a workaround for the 419 token-mismatch errors visible in recent history
(commit `d269b92` "Bugfix: avatar upload throws 419"). Excluding `csrf-token` itself is
reasonable — that endpoint exists to hand out a fresh token.

**Recommended fix.** Restore CSRF on `login` and `logout`. Solve the underlying
token-staleness problem properly — the app already has `RefreshCsrfTokenController` and a
`csrf-token` route for exactly this; the frontend should refresh the token before
submitting rather than the route being exempted.

**Effort:** small (the change is one line; verifying the token-refresh flow is the work).

---

### H4 🔴 — `enumFromValue()` operator-precedence bug

**Location:** `app/helpers.php:46-50`.

**Problem.**

```php
foreach ($enumClass::cases() as $case) {
    if ($value === $case->value ?? $case->name) {
        return $case;
    }
}
```

`===` binds tighter than `??`, so PHP parses this as:

```php
if (($value === $case->value) ?? $case->name) {
```

`($value === $case->value)` is always a boolean, never `null`, so `?? $case->name`
**never executes** — it is dead. The clear intent was:

```php
if ($value === ($case->value ?? $case->name)) {
```

i.e. "compare against the backing value, or the name for pure enums". As written, the
function also dereferences `$case->value` on **pure (non-backed) enums**, where that
property does not exist.

**Impact.** Latent: it works for backed enums (which is what the app currently uses), but
the pure-enum support the code is clearly trying to provide does not work.

**Recommended fix.** Add the parentheses: `$value === ($case->value ?? $case->name)`.

**Effort:** trivial.

---

### H5 🔴 — `models()` helper is broken for iterables

**Location:** `app/helpers.php:28-35`.

**Problem.**

```php
function models(Model|int|iterable $modelsOrIds, string $modelClass): Model|Collection
{
    if ($modelsOrIds instanceof iterable) {
        return collect($modelsOrIds)->map(fn ($id) => model($id, $modelClass));
    }

    return model($modelsOrIds, $modelClass);
}
```

`iterable` is a **type alias** (`array | Traversable`), not a class or interface.
`instanceof` expects a class/interface — used against `iterable` it does not do what is
intended (it cannot match the alias). The iterable branch is therefore effectively never
taken, and an array argument falls through to `model()`, which is typed `Model|int` and
will `TypeError` on an array.

**Impact.** `models()` is broken for its primary purpose (mapping a list of ids). If
nothing currently calls it with an iterable the bug is dormant — but it is still wrong.

**Recommended fix.** Use the dedicated function: `if (is_iterable($modelsOrIds))`.

**Effort:** trivial.

---

### H6 🔴 — GLPK timeout: float config silently truncated to `int`

**Location:** `config/lottery.php:72`, `app/Services/Lottery/Solvers/Glpk/TaskRunners/MinSatisfaction.php:79`,
`app/Services/Lottery/Solvers/Glpk/TaskRunners/TaskRunner.php:64`,
`app/Services/Lottery/Solvers/Glpk/lib/Process.php:18`.

**Problem.** The config declares a sub-second phase-1 timeout, explicitly a float:

```php
// config/lottery.php:72
'glpk_phase1_timeout' => env('GLPK_PHASE1_TIMEOUT', 0.5),  /** float, seconds */
```

But the runner chain types the timeout as `int` the whole way down:

- `MinSatisfaction::findMinSatisfactionWithGlpk(LotterySpec $spec, int $timeout)`
- `TaskRunner::runGlpk(int $timeout, …)`
- `Process::__construct(int $timeout)`

`0.5` passed into an `int` parameter is truncated to `0` and emits a PHP deprecation
("Implicit conversion from float … to int loses precision"). The intended 0.5-second
phase-1 budget becomes `0`.

The same float→int truncation happens on the **live** Hybrid path: `HybridDistribution`
computes `$stepTimeout = 2 * $timeout / $iterations` (a float, often fractional) and passes
it to `UnitDistribution::execute(float $timeout)` → `runGlpk(int $timeout)`.

**Impact.** The configured phase-1 timeout does not take effect as intended; fractional
step timeouts in the Hybrid path are truncated; PHP deprecation noise on every GLPK call.

**Recommended fix.** Make `timeout` a `float` consistently through `runGlpk()` and
`Process`, or convert the contract to integer **milliseconds** end-to-end. Note GLPK's
`--tmlim` only accepts integer seconds, so milliseconds would need conversion at the
command-building step anyway — a float seconds value passed to `--tmlim` as `%d` is the
cleaner target.

**Effort:** small.

---

### H7 🔴 — `glpk_phase1_max_size` default `0` disables the GLPK phase-1 path

**Location:** `config/lottery.php:73`, `app/Services/Lottery/Solvers/Glpk/Glpk.php:31,42`.

**Problem.**

```php
// config/lottery.php:73
'glpk_phase1_max_size' => env('GLPK_PHASE1_MAX_SIZE', 0),   /** int, spec size (#families) */
```

```php
// Glpk.php:31
$this->phase1MaxSize = $config['glpk_phase1_max_size'] ?? 25;
```

```php
// Glpk.php:42
if ($spec->familyCount() >= $this->phase1MaxSize) {
    return $this->hybridDistribution($manifest, $spec);
}
```

The config key always exists with value `0` (env default), so `?? 25` never applies —
`phase1MaxSize` is `0`. Then `familyCount() >= 0` is **always true**, so every spec is
routed to `hybridDistribution()`. The `glpkDistribution()` branch, and with it the
`GlpkDistribution` task runner and the GLPK-based phase-1 (`MinSatisfaction::execute()` /
`findMinSatisfactionWithGlpk()`), is **dead code** under the default configuration.

**Impact.** Either (a) the GLPK phase-1 path is unintentionally disabled, or (b) it is
intentionally disabled and a meaningful amount of code is now dead. The `?? 25` fallback
is misleading either way.

**Recommended fix.** Decide intent:

- If phase-1 GLPK should run for small specs → set the default to `25` (matching the
  fallback) and fix H6 so the phase-1 timeout works.
- If binary-search-only is the intended strategy → delete `GlpkDistribution`,
  `MinSatisfaction::findMinSatisfactionWithGlpk()`, and the `glpkDistribution()` branch,
  and remove the misleading `?? 25`.

**Effort:** small (decision-dependent; deletion path is medium).

---

## 5. Medium-priority findings

### M1 🟠 — ~75 files carry an unresolved `// Copilot - Pending review` marker

A grep for `Copilot - Pending review` matches ~75 files spanning `app/` and `tests/` —
the GLPK solver internals, the Broadcast service, the Form service library, the benchmark
console commands, and the large majority of the test suite.

For a thesis project this matters beyond hygiene: a substantial portion of the codebase is
AI-generated and explicitly flagged as never human-reviewed. The author should review and
take ownership of this code, then remove the markers. This document covers the parts I
read; the marker list is a good worklist for the rest.

**Effort:** medium (it's review work, not code work).

---

### M2 🟠 — Dead code in `LotteryOrchestrator::reportException()`

**Location:** `app/Services/Lottery/LotteryOrchestrator.php:151-158`.

```php
protected function reportException(Throwable $exception, string $errorType): void
{
    $exception instanceof LotteryExecutionException
        ? $exception->getUserMessage()
        : __('lottery.execution_failed');

    report($exception);
}
```

The ternary computes a message and **discards it** — the result is never assigned, logged,
or returned. Only `report($exception)` has an effect. Either the message was meant to be
used (logged with context?) or the expression should be deleted.

**Effort:** trivial.

---

### M3 🟠 — Dead `@deprecated` method `LotterySpec::sanitizeUnitIdArray()`

**Location:** `app/Services/Lottery/DataObjects/LotterySpec.php:129-149`.

The method is annotated `@deprecated No longer used - kept for backward compatibility`,
but it is `protected` — there are no external callers a "backward compatibility" promise
could apply to, and there are no internal callers. It is simply dead. Delete it.

**Effort:** trivial.

---

### M4 🟠 — Raw interpolated `DELETE` in `PreferencesService`

**Location:** `app/Services/Lottery/PreferencesService.php:74-77`.

```php
DB::unprepared("
    DELETE FROM unit_preferences
    WHERE family_id = {$family->id} AND unit_id IN ({$invalidUnitIds->join(',')})
");
```

The interpolated values are integers sourced from the database (`$family->id`, and unit
ids from a prior query), so this is **not currently exploitable**. But `DB::unprepared()`
with string interpolation is a fragile pattern — one future change to where those ids come
from turns it into SQL injection. It is also inconsistent with the `SELECT` two lines
above it (`PreferencesService.php:65-68`), which *is* correctly parameterized.

**Recommended fix.**

```php
DB::table('unit_preferences')
    ->where('family_id', $family->id)
    ->whereIn('unit_id', $invalidUnitIds)
    ->delete();
```

**Effort:** small.

---

### M5 🟠 — `app/Policies/README.md` contradicts the actual policy code

**Location:** `app/Policies/README.md` vs `app/Policies/UnitPolicy.php` (and `ProjectPolicy.php`).

The README's authorization matrix documents, for Units:

- `update` / `delete`: *"Admin managing unit's project — `$user->asAdmin()?->manages($unit->project)`"*
- `restore`: *"❌ Not defined — Defaults to super admin only"*

The actual `UnitPolicy`:

```php
public function update(User $user): bool { return $user->isAdmin(); }   // no manages() check
public function delete(User $user): bool { return $user->isAdmin(); }   // no manages() check
public function restore(User $user): bool { return $user->isAdmin(); }  // IS defined
```

So the README is wrong on three rows. `ProjectPolicy` shows the same pattern — README says
`manages($project)`, code does plain `isAdmin()`.

**Is tenancy actually broken?** No — cross-project access is currently blocked because
route-model binding resolves `Unit`/`Project` through their global query scopes
(`ProjectScope`, the `available` scope on `Project`), so an admin can only ever obtain a
model from a project they belong to. But the policies themselves provide **no
defense-in-depth**: if a model ever reaches a policy check without having gone through a
scoped query (eager-loaded, `withoutGlobalScopes()`, a future code path), the policy will
not catch it.

**Recommended fix.** Pick one and make them consistent: either add the `manages()` checks
to the policies (defense-in-depth, matches the documented intent), or correct the README
to describe what the code actually does. Adding the checks is the safer choice.

**Effort:** small.

---

### M6 🟠 — Temp-file TOCTOU window in `Files`

**Location:** `app/Services/Lottery/Solvers/Glpk/lib/Files.php:38-58`, `97-111`.

Both `write()` and `reserveSolutionPath()` follow the pattern:

```php
$tempFile = tempnam($this->tempDir, $prefix);  // safely creates a unique file
unlink($tempFile);                              // ...then deletes it
$file = $tempFile . $suffix;                    // predictable name
file_put_contents($file, $content);             // write to the new name
```

Between `unlink()` and the write, the path `$tempFile.$suffix` is predictable and does not
exist — a classic time-of-check/time-of-use window. On a shared `temp_dir` a local
attacker could pre-create that path as a symlink and redirect the write. `GLPK_TEMP_DIR`
defaults to `sys_get_temp_dir()` (`config/lottery.php:63`), which is typically world-writable.

**Recommended fix.** Keep the file `tempnam()` created and write to it directly (drop the
suffix requirement, or create a per-run private subdirectory with `0700` perms and place
all artifacts there).

**Effort:** small.

---

### M7 🟠 — Undefined variable `$unitType` in `UnitController::store()`

**Location:** `app/Http/Controllers/Resources/UnitController.php:49-61`.

```php
if ($request->new_type_name && $request->new_type_description) {
    $unitType = currentProject()->unitTypes()->create([...]);
}

return currentProject()->units()->create([
    ...
    'unit_type_id' => $unitType->id ?? $request->unit_type_id,
]);
```

When no new type is being created, `$unitType` is never defined, so `$unitType->id` reads
a property on an undefined variable — two PHP warnings ("Undefined variable",
"Attempt to read property 'id' on null") before `??` falls back. The logic still produces
the right value, but it is warning noise.

**Recommended fix.** Initialize `$unitType = null;` before the `if`.

**Effort:** trivial.

---

### M8 🟠 — Inconsistent null-safety around members / unit types

**Locations:** `app/Http/Controllers/LotteryController.php:35` vs `:72`;
`app/Http/Requests/UpdateLotteryPreferencesRequest.php:20`;
`app/Services/Lottery/PreferencesService.php:121,126`.

Two related fragilities:

1. `LotteryController::index()` uses `$request->user()->asMember()?->family` (null-safe),
   but `LotteryController::preferences()` uses `$request->user()->asMember()->family`
   (**not** null-safe). `asMember()` returns `null` for an admin — a non-member reaching
   `preferences()` would 500. It is currently guarded by `UpdateLotteryPreferencesRequest::authorize()`
   (`isMember()`), so it is latent, but the inconsistency is a trap.

2. `UpdateLotteryPreferencesRequest::rules()` and `PreferencesService::validateBeforeUpdate()`
   dereference `$family->unitType->units()`. `Family.unit_type_id` is nullable
   (`Family::unitType()` is a plain `belongsTo`); a family with no unit type would crash
   here with a null dereference.

**Recommended fix.** Decide whether a family without a unit type is a valid state. If yes,
guard these paths. If no, enforce it at the DB/validation layer. Either way, make the
null-safety consistent.

**Effort:** small.

---

### M9 🟠 — `FamilyController` cascade is not transactional

**Location:** `app/Http/Controllers/Resources/FamilyController.php:72-87`.

```php
public function destroy(Family $family): RedirectResponse
{
    $family->members()->delete();   // step 1
    $family->delete();              // step 2
    ...
}
```

`destroy()` and `restore()` both perform a two-step cascade (members, then the family)
with no surrounding transaction. A failure between the steps leaves the members and the
family in inconsistent soft-delete states. Lower impact than H2 because soft-deletes are
recoverable, but it should still be atomic.

**Recommended fix.** Wrap each in `DB::transaction()`.

**Effort:** trivial.

---

### M10 🟠 — GLPK Phase-1 objective: fractional `p` vs integer `z` (verify)

**Location:** `app/Services/Lottery/Solvers/Glpk/DataGenerator.php:107-137`,
`app/Services/Lottery/Solvers/Glpk/ModelGenerator.php:18-48`.

`DataGenerator::buildPreferenceMatrix()` adds a small random tie-breaker to every
preference rank (per family): `($rank + 1) + $tieBreaker`, where `$tieBreaker` is
`mt_rand(0, 10000) / 100000000.0` — a value in `[0, 0.0001]`. The comment states this is
"without altering semantic rank".

The Phase-1 model (`generatePhase1Model()`) declares `var z, integer` and constrains
`z >= sum p[c,v]·x[c,v]`. With fractional `p` values and an **integer** `z`, the optimal
`z` becomes `ceil(worst fractional satisfaction)`. Because `$tieBreaker` is drawn per
family and can be `0` (when `mt_rand` returns `0`), the resulting integer objective can be
either `worstRank` or `worstRank + 1` depending on the random draw — i.e. the Phase-1
output `S` is not fully deterministic, and `S` is then fed as the hard constraint
`sum p·x <= S` into Phase 2.

This *may* still be correct in practice (the `<= S` constraint with fractional `p` happens
to cap the real rank correctly in the common case), but the interaction between a random
tie-breaker, an integer objective variable, and a downstream hard constraint is subtle and
worth a deliberate check.

**Recommended action.** Verify with the existing GLPK tests (and ideally a property test)
that the max-min objective is exactly the intended worst-case rank for representative
specs. Consider making `z` continuous, or applying the tie-breaker only in the Phase-2
objective (where it breaks ties) and not in the Phase-1 constraint matrix.

**Effort:** medium (analysis + possible model change).

---

### M11 🟠 — Stress tests run as part of the default suite

**Location:** `phpunit.xml` (`<testsuite name="Stress">`).

`Stress` is listed as a regular testsuite, so a plain `pest` / `phpunit` run executes the
GLPK stress/timeout tests (`tests/Stress/Lottery/Glpk/**`) every time. These are slow by
nature and should not be in the default feedback loop.

**Recommended fix.** Move stress tests behind an excluded group (`@group stress` /
`uses()->group('stress')` plus `--exclude-group stress` in the default config), or remove
the suite from the default `<testsuites>` and run it explicitly.

**Effort:** trivial.

---

### M12 🟠 — `encryptCookies(except: ['project'])` names a non-existent cookie

**Location:** `bootstrap/app.php:36`.

```php
$middleware->encryptCookies(except: ['project']);
```

The app's current-project cookie is named **`projectId`** (`app/helpers.php:61`,
`selectProject()` → `Cookie::queue('projectId', …)`). There is no cookie named `project`.
So this exception is dead — either a leftover from a rename (`project` → `projectId`), or
a bug if the intent was for the project cookie to be readable by JavaScript (in which case
it should say `projectId`, and the `EncryptCookies`/`Context` read path would need
revisiting).

**Recommended fix.** Remove the dead exception, or correct it to `projectId` if
unencrypted access was actually intended (and document why).

**Effort:** trivial.

---

## 6. Low-priority findings

| ID | Location | Note |
|----|----------|------|
| L1 | `app/Services/Lottery/Solvers/Glpk/lib/Process.php:70` | The GLPK command appends `2>&1`, which merges stderr into stdout at the shell — the separate stderr pipe `Process` also opens then receives nothing, making that capture path redundant. |
| L2 | `Glpk.php`, `GlpkDistribution.php` | Use `app(TaskRunnerFactory::class)` mid-method even though the factory is a constructor-injected dependency in sibling classes. Inject it. |
| L3 | `app/Services/Lottery/DataObjects/LotteryManifest.php:53-81` | The constructor runs heavy per-family DB work *with side effects* (`PreferencesService` writes preferences). A DataObject mutating the database during construction is surprising. |
| L4 | `app/Models/Concerns/ProjectScope.php:104-109` | `isCalledByLaravelAuthSystem()` inspects `debug_backtrace()` for `EloquentUserProvider`. Functional but fragile across Laravel upgrades and a per-query cost. |
| L5 | `ProjectScope.php:21`, `app/Models/Member.php:104` | The `$x->id ?? $x` idiom reads a property on an `int` when given an id, emitting a warning. Use `$x instanceof Model ? $x->id : $x`. |
| L6 | `app/Models/User.php:159-166` | `fullname()`'s docblock is copy-pasted from `email()` ("Ensure email is always stored in lowercase") and is wrong. |
| L7 | `routes/web/documentation.php:6` | Comment claims the documentation routes are "accessible to everyone - guests and authenticated users", but `routes/web.php:21` wraps them in `auth`. |
| L8 | `app/Services/Lottery/Solvers/Glpk/TaskRunners/TaskRunner.php:23` | `protected $context = [];` is untyped — should be `protected array $context = []`. |
| L9 | `app/Http/Requests/FilteredIndexRequest.php:11-17` | Duplicates the parent `ProjectScopedRequest`'s `project_id` rule instead of merging with `parent::rules()`. |
| L10 | `tests/Unit/Models/REORGANIZATION.md` | A leftover planning note living inside the test tree. |

Also worth a glance: `app/Models/Model.php` uses `protected $guarded = ['id']` (permissive
mass-assignment — every column except `id` is fillable). The controllers reviewed all pass
`$request->validated()`, so input is gated in practice; just be deliberate about it as the
local `laravel-instant-api` package's auto-generated endpoints expand.

---

## 7. Test-suite assessment

### 7.1 Current architecture

- 105 PHP test files (~13.6k LOC) across `Arch`, `Unit`, `Feature`, `Stress`, `Browser`;
  plus 24 Vue/vitest files under `resources/js/tests`.
- `tests/Pest.php` wraps each Unit/Feature/Stress test in `DB::beginTransaction()` /
  `DB::rollback()`.
- `tests/TestCase.php` seeds the database **once per suite** via `loadUniverse()`, which
  loads the fixed SQL fixture `tests/Fixtures/universe.sql` (a `static $bootstrapped` flag
  prevents re-seeding).

This "seed once, roll back each test" design is deliberately fast. The trade-offs below
are the cost of that choice.

### 7.2 Quality observations

**Brittleness (the dominant issue).** Because the suite runs against one fixed SQL
fixture, the Feature/Healthcheck tests hard-code fixture identities — `Member 102`,
`Admin 11`, `Admin 12`, `Unit 1`, `Unit 4`, `Project 1/2/3` — and bake the seed's
relationships into inline comments ("Member #102 is in Project #1", "Admin #12 manages
Projects #2 and #3"). Any change to `universe.sql` silently invalidates dozens of tests.
This is fragile, but reworking it is a large refactor — noted here, not scheduled.

**Fragile bootstrap.** `TestCase::$bootstrapped` is a `public static`; `TestCaseBrowser`
resets it to `false`, and Browser tests are **not** transaction-wrapped (they mutate the
real database). The ordering between Browser and non-Browser tests is therefore
load-bearing.

**Nothing to delete.** A scan for skipped / `->todo()` / incomplete tests found none — so
there are **no useless or dead tests to remove**. The test work is hardening and filling
gaps, not deletion. (One file to eyeball by name: `tests/Browser/Healthcheck/SampleScreenshotTest.php`.)

**What is good — keep as-is.** The `tests/Unit/Lottery/**` tests (e.g.
`LotteryOrchestratorTest`) are genuinely good: deterministic (`TestSolver`), built from
constructed in-memory data rather than the seeded universe, with meaningful assertions on
picks/orphans across all phase combinations.

**Unreviewed.** The `// Copilot - Pending review` marker (M1) is on most test files too.

### 7.3 Missing tests to add — *the agreed remediation*

You asked to address the test gap by **adding missing coverage**. The concrete list,
pending your approval:

1. **`app/helpers.php`** — currently has *zero* tests and *two* bugs (H4, H5):
   - `enumFromValue()`: backed enum hit/miss, the (currently broken) pure-enum path,
     invalid value throws.
   - `models()`: single id, single model, an iterable of ids (the currently broken path),
     `withTrashed`.
   - `model()`: id vs model, `findOrFail` miss, `withTrashed`.
2. **`ExecutionService::applyResults()` / `invalidate()`** — assert atomicity (after H2's
   fix): a mid-apply failure leaves *no* partial assignment; `invalidate()` fully reverts.
3. **`Event::status()` accessor** — the `upcoming` / `ongoing` / `completed` matrix,
   including the implicit-duration boundary (`IMPLICIT_DURATION`) and null start/end dates.
4. **Policy unit tests** — policies are currently exercised only indirectly through the
   Healthcheck HTTP tests. Add direct tests per policy (`UnitPolicy`, `ProjectPolicy`,
   `FamilyPolicy`, `EventPolicy`, …) — this also pins down the M5 README/code decision.
5. **`PreferencesService::sanitizeBeforeFetch()`** — the invalid-preference pruning path
   and the `InvalidPreferences` event dispatch.
6. **Frontend** — 324 components against 24 test files is thin; at minimum add tests for
   the lottery composables under `resources/js/components/lottery/composables`.

These are proposed, not written — no test files have been created.

---

## 8. Proposed action plan (once approved)

A suggested sequence, smallest-risk first. Each item is gated on your approval of this
document; nothing here is done yet.

**Phase 1 — production safety (do first)**
- H1 — remove the temporary navigation middleware (after the frontend `USER_NAVIGATION` check).
- H3 — restore login/logout CSRF and fix the token-refresh flow.
- H2 — wrap `applyResults()` in a transaction.

**Phase 2 — confirmed bugs, isolated**
- H4, H5 — the two `helpers.php` bugs.
- M7, M9 — undefined variable; non-transactional family cascade.

**Phase 3 — GLPK decisions (need your input)**
- H6 + H7 — settle the timeout type and the `phase1_max_size` intent together; then either
  wire phase-1 GLPK up correctly or delete the dead path.
- M10 — verify the Phase-1 objective.

**Phase 4 — cleanup**
- M2, M3, M12, L-series — dead code and polish.
- M4 — parameterize the raw `DELETE`.
- M5 — reconcile the policy README with the code (recommend: add the `manages()` checks).
- M6 — temp-file handling.
- M11 — move stress tests out of the default suite.

**Phase 5 — tests**
- Add the missing coverage from §7.3.

**Ongoing**
- M1 — review the `// Copilot - Pending review` files and drop the markers.

---

## 9. Approval

This document is a draft for your review. Please annotate which findings you accept,
reject, or want to discuss. No source files will be modified until you approve a scope.

> **Reviewer's note:** the GLPK domain logic is the most intricate part of the system and
> the most consequential to get exactly right for a housing lottery. H6, H7 and M10 all
> touch it — I'd recommend pairing on those three rather than accepting a fix blind.
