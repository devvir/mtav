# Lottery engine & open lotteries
_Review of main · scope: `app/Services/LotteryService.php`, `app/Services/Lottery/**` (orchestrator, GLPK solver and runners, preferences, audit/record, data objects, exceptions), `app/Jobs/RunLottery.php`, `app/Jobs/PublishLotteryRun.php`, `app/Http/Controllers/{Manage,Member}/LotteryController.php`, `LotteryRun`/`LotteryAudit` models, lottery migrations, lottery routes. Open lotteries (`OpenLotteryService`, `OpenLotteryController`) do not exist on main, so that part of the scope was not reviewed._

## Summary
The lottery is cleanly layered: request -> `LotteryService` -> queued `RunLottery` -> `LotteryOrchestrator` -> solver, and the solver works on plain ids. Authorization is sound: routes use `scopeBindings()` and `can:update,project`, and the download re-checks `update`. Locking the project row with `lockForUpdate` and re-checking for an active run inside the transaction stops a double run. A held ("reveal") result never reaches members, and invalidations stay public. The serious problems are about fairness, and the code does not keep the promise the UI makes to every member ("first the worst-off family as well off as possible, then the whole project … only ties are drawn at random"):
- **No randomness.** The draw contains no randomness at all, so every tie is settled by database row order.
- **More units than families.** Pruning spare units by heuristic can throw away units the max-min optimum needs.
- **More families than units.** The placeholder ("mock") units make the max-min phase meaningless, so the solver just minimises the sum of ranks.

I confirmed both max-min failures by brute force. Second-tier issues: the job solves from a manifest it rebuilds, not from the one it audited; GLPK solver files pile up in one audit row, which stops lotteries for large unit types; fractional timeouts are truncated to 0; a failed run's record shows as "Final"; and there are no optimality or tie tests for unbalanced types.

## Findings

| # | Severity | Category | Title | Location |
|---|---|---|---|---|
| 03-01 | high | correctness | No randomness in the draw: the "random tie-break" is rounded away and could not work anyway, so ties always go by row order | `app/Services/Lottery/Solvers/Glpk/DataGenerator.php:117` |
| 03-02 | high | correctness | More units than families: heuristic pre-pruning drops units the max-min optimum needs | `app/Services/Lottery/Solvers/Glpk/SpecBalancer.php:32` |
| 03-03 | high | correctness | More families than units: mock units ranked n+1..n+k make the max-min phase meaningless, so the solver turns purely utilitarian | `app/Services/Lottery/Solvers/Glpk/SpecBalancer.php:56` |
| 03-04 | medium | correctness | Job solves from a freshly rebuilt manifest, not the audited INIT one, and the input freeze is check-then-act | `app/Jobs/RunLottery.php:49` |
| 03-05 | medium | performance | GLPK solver files pile up in a single audit row; large unit types exceed the DB packet limit and the run always fails | `app/Services/Lottery/Solvers/Glpk/TaskRunners/HybridDistribution.php:50` |
| 03-06 | medium | tests | No tests for optimality in unbalanced types, for tie randomness, or that the audit matches what was solved | `tests/Unit/Lottery/Glpk/MoreUnitsTest.php:40` |
| 03-07 | low | correctness | Fractional timeouts are cut to whole seconds (`--tmlim 0`); the exact phase-1 path is dead by default | `app/Services/Lottery/Solvers/Glpk/TaskRunners/MinSatisfaction.php:79` |
| 03-08 | low | correctness | The record page serves a FAILED run's never-applied assignment, labelled "Final" | `app/Services/Lottery/RunRecord.php:26` |
| 03-09 | low | security | Held result is written to the activity log, visible to superadmins before the reveal | `app/Services/LotteryService.php:126` |
| 03-10 | low | practices | Fingerprint cannot be recomputed from the downloaded record and is never anchored; its "latest" lookups return the oldest audit | `app/Services/Lottery/RunRecord.php:94` |
| 03-11 | low | performance | Manifest building runs 2 queries and a full unit load per family, while holding the project row lock (and again in the job) | `app/Services/LotteryService.php:105` |
| 03-12 | low | i18n | Failure reason is stored already translated, in the worker's locale | `app/Services/LotteryService.php:167` |
| 03-13 | low | security | Preferences payload has no size limit and the route has no throttle | `app/Http/Controllers/Member/LotteryController.php:89` |
| 03-14 | low | practices | Dead and "Copilot - Pending review" code, with docblocks that contradict the algorithm, in the fairness-critical path | `app/Services/Lottery/Solvers/Glpk/DataGenerator.php:3` |

### 03-01. No randomness in the draw: the "random tie-break" is rounded away and could not work anyway, so ties always go by row order
- **Severity / category:** high / correctness
- **Location:** `app/Services/Lottery/Solvers/Glpk/DataGenerator.php:117`, `app/Services/Lottery/Solvers/Glpk/DataGenerator.php:128`, `app/Services/LotteryService.php:100`, `app/Services/LotteryService.php:102`, `app/Services/Lottery/LotteryOrchestrator.php:78`, `app/Services/Lottery/PreferencesService.php:73`, `resources/js/pages/Member/Lottery.tsx:129`
- **Problem:**
  - **The only random input is rounded away.** The only random value given to GLPK is `$tieBreaker = mt_rand(0, 10000) / 100000000.0`, which is at most 0.0001. It is added to the family's ranks, and each rank is then printed with `sprintf('%.3f', $rank)`, which always gives `N.000`. So the value never reaches the solver.
  - **It could not break ties even unrounded.** It is one constant per family, added to all of that family's ranks. Each family receives exactly one unit, so every feasible assignment shifts by the same total and the optimum does not change.
  - **Everything else is deterministic.** GLPK is deterministic. The manifest is built from `families()->get()` and `units()->get()` with no ORDER BY, so the variable order follows the database's order (in practice the primary key). The ranking order for units a family did not rank is `sha256(app.key:family:unit)`: fixed, and computable by anyone who holds APP_KEY.
  - **The orphan pass is decided entirely by this order.** It gives every leftover family the same ranking (`[$familyId => $units]`), so which leftover family gets which leftover unit, and who goes without, is a pure tie every time.
  - **Nothing is recorded.** No seed is generated, stored or audited.
  - **The UI says otherwise.** All five locales tell members that "only ties are drawn at random".
- **Failure scenario:**
  - Families A (id 10) and B (id 11) both rank `[X, Y]`, and only X and Y exist. Both assignments give worst place 2 and total 3, so GLPK returns the same winner for X on every run, decided by variable order. Invalidating and re-running gives an identical result.
  - In a mismatch-allowed run, the leftover families from several types compete for leftover units purely by their position in the orphan list.
  - Anyone with DB access can predict the outcome before the "draw" by running the same pipeline.
- **Recommended fix:** Generate a per-run seed with `random_bytes()` when the run starts and record it in the INIT audit. Use it to permute the family and unit order given to the solver, and/or add a seeded third phase: minimise seeded random weights, subject to the phase-1 and phase-2 optima. The draw stays reproducible from the audit while ties become genuinely random. Remove the dead tie-breaker.
- **Verification note:** Read DataGenerator and the manifest query. `php -r` confirms `sprintf('%.3f', 1 + 0.0001) === '1.000'`. grep finds no other randomness source in `app/Services/Lottery` except `RandomSolver` (dev only).

### 03-02. More units than families: heuristic pre-pruning drops units the max-min optimum needs
- **Severity / category:** high / correctness
- **Location:** `app/Services/Lottery/Solvers/Glpk/SpecBalancer.php:32`, `app/Services/Lottery/Solvers/Glpk/SpecBalancer.php:89`, `app/Services/Lottery/Solvers/GlpkSolver.php:65`, `app/Services/Lottery/Solvers/Glpk/ModelGenerator.php:122`
- **Problem:**
  - **The pre-filter can lose the optimum.** `pruneWorstUnits()` first limits the candidates to the union of every family's top-d choices, for the smallest d whose union reaches M (the number of families). If the union has exactly M units, GLPK is skipped. That union only proves the optimal worst place z* is at least d. The optimum can need a unit that lies outside the top-d union, so the docblock's claim that this "maintains max-min fairness" is false.
  - **The unit-selection model ignores the total.** When it does run, it only `minimize resultado: z`. Among subsets with equal z it can keep one that forces a worse total rank, which breaks the promised second criterion.
  - **Ranks are compressed after filtering.** `LotterySpec::filter()` renumbers ranks within the kept units, so the solver optimises over compressed ranks, not the places the record shows.
- **Failure scenario:**
  - Units 0..6 and 4 families, ranked F1=[2,4,0,5,6,1,3], F2=[6,0,3,2,4,5,1], F3=[6,0,1,5,4,2,3], F4=[0,6,5,3,1,2,4].
  - Top-1 gives {0,2,6} (3 units, fewer than 4). Top-2 gives {0,2,4,6}, exactly 4, so the candidates are fixed with no GLPK step.
  - The optimum over all units has worst place 3 and total 6 (F1=2, F2=6, F3=1, F4=0). Restricted to {0,2,4,6}, the best possible is worst place 4, total 8.
  - Result: a family gets its 4th choice where its 3rd was achievable. This happens whenever an admin confirms `mismatch-allowed` for a type with spare units.
- **Recommended fix:** Drop the top-d pre-filter, or use it only as a lower bound. Solve the more-units case directly as a rectangular assignment (`sum_c x[c,v] <= 1`, no unit-selection pre-phase): first minimise max rank over all units, then minimise total rank subject to that. Spare units are simply the unassigned ones. Add brute-force cross-check tests on small random instances.
- **Verification note:** Brute force in Python over all assignments reproduced exactly (3, 6) unrestricted vs (4, 8) restricted, and the heuristic union {0,2,4,6} at depth 2. Code path read in `GlpkSolver::executeMoreUnits` -> `SpecBalancer::pruneWorstUnits`.

### 03-03. More families than units: mock units ranked n+1..n+k make the max-min phase meaningless, so the solver turns purely utilitarian
- **Severity / category:** high / correctness
- **Location:** `app/Services/Lottery/Solvers/Glpk/SpecBalancer.php:56`, `app/Services/Lottery/Solvers/Glpk/SpecBalancer.php:127`, `app/Services/Lottery/Solvers/GlpkSolver.php:83`, `app/Services/Lottery/Solvers/Glpk/TaskRunners/HybridDistribution.php:80`
- **Problem:**
  - **Mock ranks are not 999.** `addMockUnits()` appends `MOCK_1..MOCK_k` to the end of every family's ranking, so `MOCK_i` has rank n+i, not the 999 the docblock says.
  - **Phase 1 is fixed at a meaningless value.** Every unit must be assigned, so some family always takes `MOCK_k`. The binary search (upper bound `count($spec->units)` = n+k) therefore always returns S = n+k, which is larger than any real rank. The phase-2 constraint `<= S` never binds.
  - **Phase 2 only minimises the sum.** The mock ranks add up to the same constant whoever gets them, so phase 2 just minimises the total real rank.
  - **Where it applies.** Max-min protection disappears for every type where families outnumber units, and in the orphan pass when that pass has more families than units.
  - **Who goes without is not a draw.** The family left without a unit is chosen by sum arithmetic, not by a draw.
- **Failure scenario:**
  - Units A..D and 5 families, ranked F1=[B,A,C,D], F2=[D,C,B,A], F3=[D,C,A,B], F4=[C,B,D,A], F5=[D,C,B,A].
  - Both sum-minimal assignments give one family its 3rd choice (e.g. F1=B, F2=D, F3=A(3), F4=C, F5 without a unit).
  - An assignment exists where no family with a unit is below its 2nd choice (F1=A, F3=C, F4=B, F5=D, F2 without a unit).
  - The record page then shows "worst place 3" where 2 was achievable.
- **Recommended fix:** Do not model "no unit" as a ranked unit. Either leave mock assignments out of the z constraint (`z >= sum over real v of p*x`), or solve a rectangular model with `sum_c x[c,v] <= 1` and exactly one unit or none per family. Decide who goes without among the tied candidates with the seeded randomness from 03-01.
- **Verification note:** Brute force: every sum-minimal assignment has worst real rank 3, while the max-min optimum is 2. Read `appendMockUnitsToPreferences` and the binary-search bounds.

### 03-04. Job solves from a freshly rebuilt manifest, not the audited INIT one, and the input freeze is check-then-act
- **Severity / category:** medium / correctness
- **Location:** `app/Jobs/RunLottery.php:49`, `app/Services/LotteryService.php:81`, `app/Services/Lottery/PreferencesService.php:53`, `app/Services/Lottery/PreferencesService.php:65`, `app/Models/Project.php:247`, `app/Http/Controllers/Manage/FamilyController.php:25`
- **Problem:**
  - **Two different manifests.** `run()` records `manifest($run)` in the INIT audit inside the transaction that locks the project row. `RunLottery::handle()` then calls `$lottery->manifest($run)` again and solves from that new copy. `RunRecord` (families, places) and the fingerprint use the INIT copy. The docblock at `Project.php:247-249` says "what it decides from is what its audit recorded".
  - **The guards are not atomic.** The guards that freeze the inputs (`PreferencesService::rank()` via `lotteryLocked()`, `FamilyController::store` via `ensureLotteryNotRunning()`, `FamilyService::accept`/`leave`, `createOwnFamily` in the policy) are check-then-act. None of them takes the project-row lock that `run()` holds.
  - **The race window.** Under MariaDB REPEATABLE READ, a write that passed its check before the run committed can commit after INIT read the data. The job then reads the new data.
- **Failure scenario:**
  1. At the live lottery, a member drops a unit into first place just as the admin presses Run.
  2. `rank()` passes `lotteryLocked()`, `run()` reads the old preferences into INIT and commits, then the member's `sync()` commits.
  3. The job solves with the new ranking, while the record page computes places against the old one (wrong "N of M").
  4. An auditor who re-solves from the downloaded INIT manifest gets a different assignment, which looks like tampering.

  The same happens with an admin creating a family in that window: the family receives a unit but does not appear in the record at all.
- **Recommended fix:** Solve exactly what was audited: pass the `LotteryManifest` to the job, or read it back from the INIT audit, instead of calling `manifest()` again. Also take `Project::whereKey()->lockForUpdate()` (or a shared lock) inside `rank()`, family creation and the other guarded writes, so their checks serialise with `run()`.
- **Verification note:** Read `RunLottery::handle` (second `manifest()` call), `LotteryService::run`, and every `lotteryLocked`/`ensureLotteryNotRunning` caller (grep). None of them takes a lock.

### 03-05. GLPK solver files pile up in a single audit row; large unit types exceed the DB packet limit and the run always fails
- **Severity / category:** medium / performance
- **Location:** `app/Services/Lottery/Solvers/Glpk/TaskRunners/HybridDistribution.php:50`, `app/Services/Lottery/Solvers/Glpk/TaskRunners/HybridDistribution.php:115`, `app/Services/Lottery/Solvers/Glpk/TaskRunners/TaskRunner.php:100`, `app/Services/Lottery/Solvers/Glpk/lib/Files.php:71`, `app/Services/Lottery/Solvers/Glpk/lib/Files.php:149`, `app/Services/Lottery/Solvers/Glpk/Glpk.php:101`
- **Problem:**
  - **One runner accumulates every file.** `searchBestDistribution()` reuses one `UnitDistribution` instance for every binary-search step. Its `Files` object keeps the full text of every `.mod`, `.dat` and `.sol` it has written or read (infeasible steps included), and `getArtifacts()` is never cleared (`clearArtifacts()` is unused).
  - **All of it lands in one audit row.** `mergeTaskResults` puts the final feasible step's accumulated artifacts in the metadata, and `Glpk::auditTask` writes it as a single `lottery_audits` JSON row.
  - **Size grows with N² times the number of steps.** `glpsol --output` lists every `x[c,v]` column, so each `.sol` is N² lines (about 90 bytes each once names exceed 12 characters and wrap). The hybrid path runs about log2(N)+1 steps.
  - **Hybrid is always used.** It is the only path by default; see 03-07.
- **Failure scenario:**
  - An approved project (unapproved ones are capped at 50 families) has a unit type with about 150 families.
  - About 8 steps x (~2 MB `.sol` + ~0.2 MB `.dat`) puts over 16 MB in one INSERT, above MariaDB's default 16 MB `max_allowed_packet`. `docker/services/mysql/base.yml` sets no override.
  - The audit insert throws, `RunLottery` catches it and marks the run FAILED, and every retry fails the same way.
  - Smaller lotteries still store MBs per run, which `RunRecord::full()` loads and pretty-prints in memory on download.
- **Recommended fix:** Use a fresh runner per step, or clear artifacts between steps. By default store only a SHA-256 of each file plus the final `.dat` and the parsed solution. If raw files are needed for forensics, keep them compressed on disk or in object storage.
- **Verification note:** Read Files/TaskRunner/HybridDistribution. The runner instance is created once at `HybridDistribution.php:50`, and artifacts are keyed by unique tempnam basenames, so they accumulate. The size estimate comes from GLPK's per-column output format.

### 03-06. No tests for optimality in unbalanced types, for tie randomness, or that the audit matches what was solved
- **Severity / category:** medium / tests
- **Location:** `tests/Unit/Lottery/Glpk/MoreUnitsTest.php:40`, `tests/Unit/Lottery/Glpk/MoreFamiliesTest.php:14`, `tests/UseCases/Lottery/ExecutionTest.php:88`, `tests/Unit/Lottery/Glpk/DataGeneratorTest.php:79`
- **Problem:**
  - **Optimality is tested once, in the easy case.** The only optimality test ("protects the worst-off family first") is a balanced 3x3 instance with a unique optimum.
  - **The pruning test cannot fail.** "prunes worst units maintaining fairness" uses a case where the top-d union already contains the optimum.
  - **The more-families tests check counts only.** MoreFamilies tests check counts and orphans, not optimality.
  - **The tie-break test asserts the opposite.** DataGeneratorTest's comment asserts the tie-breaker is there "to prevent degeneracy", while only checking integer parts.
  - **Ties and audit consistency are untested.** Nothing checks that ties vary or that the solved manifest equals the INIT audit.
- **Failure scenario:** The regressions in 03-01..03-04 (worst place 4 instead of 3, worst place 3 instead of 2, no tie randomness, solved manifest different from the audited one) all pass CI today.
- **Recommended fix:**
  - Add brute-force cross-check property tests on small random instances (balanced, more units, more families), asserting the (max rank, total rank) optimum.
  - Add a seeded test that identical-ranking families split wins across seeds.
  - Add a test that the manifest the job solves equals the INIT audit's.
- **Verification note:** Read the listed tests. None asserts optimality outside the 3x3 balanced case.

### 03-07. Fractional timeouts are cut to whole seconds (`--tmlim 0`); the exact phase-1 path is dead by default
- **Severity / category:** low / correctness
- **Location:** `app/Services/Lottery/Solvers/Glpk/TaskRunners/MinSatisfaction.php:79`, `app/Services/Lottery/Solvers/Glpk/TaskRunners/TaskRunner.php:64`, `app/Services/Lottery/Solvers/Glpk/TaskRunners/HybridDistribution.php:24`, `app/Services/Lottery/Solvers/Glpk/lib/Process.php:115`, `app/Services/Lottery/Solvers/Glpk/Glpk.php:42`, `config/lottery.php:73`, `config/lottery.php:74`
- **Problem:**
  - **The phase-1 timeout becomes 0.** `glpk_phase1_timeout` defaults to 0.5 and reaches `findMinSatisfactionWithGlpk(..., int $timeout)`. PHP truncates it to 0 and emits "Implicit conversion … loses precision". glpsol gets `--tmlim 0`, and the PHP failsafe becomes `ceil(0*1.2)=0`, which trips as soon as `time()` ticks over.
  - **Short step timeouts also become 0.** `stepTimeout = 2*timeout/iterations` is truncated the same way by `runGlpk(int ...)`.
  - **The exact path never runs by default.** `glpk_phase1_max_size` defaults to 0, so `familyCount() >= 0` always picks the hybrid path. The constructor fallback of 25 suggests a different intent.
- **Failure scenario:**
  - With `GLPK_TIMEOUT=3` and a 100-family type (8 iterations), the step timeout is 0.75, truncated to 0. Any step that crosses a wall-clock second is killed with `GlpkTimeoutException`. That exception is not caught by `searchBestDistribution`, so the whole lottery fails.
  - With `GLPK_PHASE1_MAX_SIZE=25`, every small type wastes a glpsol call, logs deprecations, and falls back silently.
- **Recommended fix:** Carry timeouts as floats or milliseconds through `Process`/`runGlpk`, round `--tmlim` up to at least 1 s, and cast config values explicitly. Either fix the `phase1_max_size` default or delete the unused exact phase-1 path.
- **Verification note:** `php -r` on PHP 8.3 confirms `f(int $t)` with 0.5 and 0.75 returns 0 with a deprecation. No file in the chain declares `strict_types`.

### 03-08. The record page serves a FAILED run's never-applied assignment, labelled "Final"
- **Severity / category:** low / correctness
- **Location:** `app/Services/Lottery/RunRecord.php:26`, `app/Http/Controllers/Member/LotteryController.php:110`, `resources/js/pages/Member/LotteryRecord.tsx:19`, `resources/js/pages/Member/LotteryRecord.tsx:75`
- **Problem:**
  - **`exists()` lets FAILED runs through.** It excludes only RUNNING and requires the INIT and PROJECT_EXECUTION audits. The orchestrator writes PROJECT_EXECUTION before the result is applied, so a run that fails afterwards still "exists". That happens when `complete()` throws inside `RunLottery`, or when `PublishLotteryRun` exhausts its 3 tries.
  - **The page shows it as final.** `LotteryRecord.tsx` types status as `'completed' | 'invalidated'` and shows the green "Final" pill for anything that is not invalidated.
  - **The UI hides the link, not the page.** The admin tools only link completed and invalidated runs, but the URL takes sequential ids and any project member can open it.
- **Failure scenario:**
  1. With `LOTTERY_SECONDS=30`, `PublishLotteryRun` fails 3 times (DB outage), so the run becomes FAILED and no unit is written. This is the case covered by `PaceTest.php:66`.
  2. A member opens `/projects/{p}/lottery/runs/{id}` and sees every family's "unit" with a "Final" badge.
  3. After the admin re-runs, the real assignment differs.
- **Recommended fix:** Have `exists()` require status COMPLETED or INVALIDATED (or render FAILED as "not applied"), and handle `failed` in `LotteryRecord.tsx`.
- **Verification note:** Read `RunRecord::exists`, `LotteryOrchestrator::execute` (audit before return), `LotteryService::complete/fail` and the record page.

### 03-09. Held result is written to the activity log, visible to superadmins before the reveal
- **Severity / category:** low / security
- **Location:** `app/Services/LotteryService.php:126`, `app/Models/LotteryRun.php:17`, `app/Models/Concerns/RecordsActivity.php:20`, `app/Services/ActivityLog.php:20`, `app/Http/Controllers/Platform/ActivityController.php:44`
- **Problem:**
  - **The held picks go into the activity log.** `solved()` stores the full picks in `lottery_runs.result` while the run is still RUNNING. `LotteryRun` uses `RecordsActivity`, and `ActivityLog::changes()` only excludes the fields in `SECRET`, so `result` (every family => unit) is written to `activities.changes`.
  - **The platform console shows it.** `Platform/ActivityController` displays those changes.
  - **This breaks a stated promise.** PaceTest promises the held record is kept away "from everyone". Every run also duplicates the picks into the activity table.
- **Failure scenario:** During the reveal window (20-30 s, longer if the queue is behind), a superadmin browsing the activity feed sees who gets which unit before the project does. A project admin who is also a superadmin sees it before their own reveal ceremony.
- **Recommended fix:** Exclude `result` from `LotteryRun`'s activity changes (a per-model ignore list in `ActivityLog`), or keep the held result out of the model, for example by reading it from the PROJECT_EXECUTION audit at publish time.
- **Verification note:** Traced `update(['result' => ...])` -> `RecordsActivity::updated` -> `ActivityLog::changes` (no exclusion for `result`) -> `ActivityController` output.

### 03-10. Fingerprint cannot be recomputed from the downloaded record and is never anchored; its "latest" lookups return the oldest audit
- **Severity / category:** low / practices
- **Location:** `app/Services/Lottery/RunRecord.php:94`, `app/Services/Lottery/RunRecord.php:96`, `app/Services/Lottery/RunRecord.php:114`, `app/Models/LotteryRun.php:64`, `app/Http/Controllers/Member/LotteryController.php:138`
- **Problem:**
  - **The hash is computed only on view.** `fingerprint()` hashes the raw DB text of the INIT and PROJECT_EXECUTION audits each time the page is viewed. It is never stored or sent at completion (the completion notification does not carry it), so its only anchor is a member writing it down, as the UI asks.
  - **The download does not contain the hashed bytes.** The download re-encodes the decoded audits with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`, so an auditor cannot reproduce the hash.
  - **The "latest" lookups return the oldest audit.** The `audits()` relation already has `orderBy('id')`, and adding `->latest('id')` yields `ORDER BY id ASC, id DESC`. So `fingerprint()` and `audit()` pick the *oldest* audit of each type. This is harmless today (one INIT and one PROJECT_EXECUTION per run, `$tries = 1`) but contradicts the intent.
- **Failure scenario:**
  - An auditor runs sha256 over the downloaded INIT and PROJECT_EXECUTION records and gets a different value from the page, which looks like tampering.
  - Conversely, someone with DB access who rewrites both audits (and the units) right after completion, before anyone noted the hash, leaves no trace.
- **Recommended fix:** Compute the fingerprint once when the run completes, store it on `lottery_runs`, and include it in the completion notification. Ship the exact hashed strings (or a documented canonical serialization) in the download. Use `reorder('id', 'desc')`, and consider a unique index on (`lottery_run_id`, `type`) for INIT and PROJECT_EXECUTION.
- **Verification note:** Read `RunRecord`, the download controller and `LotteryRun::audits()`. The duplicate-ordering semantics are standard SQL.

### 03-11. Manifest building runs 2 queries and a full unit load per family, while holding the project row lock (and again in the job)
- **Severity / category:** low / performance
- **Location:** `app/Services/LotteryService.php:105`, `app/Services/Lottery/PreferencesService.php:29`, `app/Services/LotteryService.php:66`, `app/Jobs/RunLottery.php:49`
- **Problem:**
  - **Queries grow with families times units.** `manifest()` calls `PreferencesService::ranking()` per family. Each call runs `Unit::where('unit_type_id', …)->get()`, hydrating full models for the whole type, plus a `unit_preferences` query. That is 2F queries and F x U model hydrations.
  - **It runs while the project is locked.** In `run()` this happens in the HTTP request while holding `lockForUpdate` on the projects row, and `RunLottery` repeats it.
  - **No size cap for approved projects.** Approved projects have no families limit.
- **Failure scenario:** An approved project has 400 families in one type: about 800 queries and 160k `Unit` models hydrated in the request, with the project row locked (blocking settings and other writes to it) for the duration, then the same again in the worker.
- **Recommended fix:** Build the manifest in bulk: one query for the project's unit ids grouped by type, one for all `unit_preferences` of its families, then compute rankings in memory (the default order is a pure function). Build it once and hand it to the job (this also fixes 03-04).
- **Verification note:** Read `manifest()` and `ranking()`. The per-family queries are visible in the code.

### 03-12. Failure reason is stored already translated, in the worker's locale
- **Severity / category:** low / i18n
- **Location:** `app/Services/LotteryService.php:167`, `app/Jobs/RunLottery.php:57`, `app/Jobs/RunLottery.php:69`, `app/Http/Resources/LotteryRunResource.php:24`
- **Problem:** `fail()` stores `failure` as a string already run through `__()` (`getUserMessage()` / `__('lottery.execution_failed')`). That happens inside the queue worker, which runs in the app default locale. `LotteryRunResource` then shows this fixed string to every viewer.
- **Failure scenario:** In a project whose members use pt_BR or fr, a failed run's reason appears in the platform default language for everyone, and it does not change when they switch language.
- **Recommended fix:** Store the key and params (the exception already holds `userMessageKey` and `userMessageParams`) and translate in the resource for the current locale.
- **Verification note:** Read `LotteryExecutionException::getUserMessage` and the job's two `fail()` calls.

### 03-13. Preferences payload has no size limit and the route has no throttle
- **Severity / category:** low / security
- **Location:** `app/Http/Controllers/Member/LotteryController.php:89`, `app/Services/Lottery/PreferencesService.php:58`, `routes/web.php:209`
- **Problem:** The validation rules are `'preferences' => ['present','array']` and `'preferences.*' => ['integer']`, with no `max`. `rank()` then runs `array_unique`/`array_map` and a `whereIn()` over every id before it rejects foreign ids. `PUT lottery/preferences` has no throttle, unlike the other member write routes.
- **Failure scenario:** A family member PUTs a JSON body with 100k integers. Validation iterates over all of them, then `whereIn` binds 100k placeholders. Under real prepared statements this exceeds MariaDB's 65,535-placeholder limit and returns a 500. Repeated requests tie up PHP workers.
- **Recommended fix:** Add `max:` (for example 1000, or the type's unit count) and `distinct` to the rules, and a `throttle` on the route.
- **Verification note:** Read the validation rules and the route definition (no throttle middleware on line 209).

### 03-14. Dead and "Copilot - Pending review" code, with docblocks that contradict the algorithm, in the fairness-critical path
- **Severity / category:** low / practices
- **Location:** `app/Services/Lottery/Solvers/Glpk/DataGenerator.php:3`, `app/Services/Lottery/Enums/ReportType.php:1`, `app/Services/Lottery/Enums/ExecutionStatus.php:1`, `app/Services/Lottery/Solvers/Glpk/Logger.php:1`, `app/Services/Lottery/DataObjects/LotterySpec.php:135`, `app/Services/Lottery/Solvers/Glpk/TaskRunners/MinSatisfaction.php:44`, `app/Services/Lottery/Solvers/Glpk/Enums/FeasibilityResult.php:25`, `app/Services/Lottery/Solvers/Glpk/lib/Files.php:166`, `app/Policies/ProjectPolicy.php:96`, `app/Policies/ProjectPolicy.php:101`
- **Problem:**
  - **Unused code.** These are referenced nowhere in `app` or `tests`: `ReportType`, `ExecutionStatus`, `Glpk\Logger`, `LotterySpec::sanitizeUnitIdArray`, `MinSatisfaction::binarySearchGenerator` (duplicated by HybridDistribution's), `FeasibilityResult::TIMEOUT`, `Files::clearArtifacts`, and the `runLottery`/`invalidateLottery` policy abilities (the routes use `can:update,project`).
  - **Unreviewed files.** Eight solver files still carry a "// Copilot - Pending review" header: DataGenerator, ModelGenerator, SolutionParser, Process, TaskRunner, TaskResult, FeasibilityResult and Logger.
  - **Docblocks that contradict the code.** Several describe behaviour the code does not have: the random tie-breaker, mock rank 999, and pruning that "maintains max-min fairness".
- **Failure scenario:**
  - Reviewers trust the docblocks, and the three high findings above sit behind comments claiming the opposite.
  - A later change adds checks to `runLottery()` expecting it to guard the run route, but it is never consulted.
- **Recommended fix:** Delete the unused types and methods. Either wire the dedicated policy abilities into the routes or remove them. Review the "Pending review" files and correct their docblocks to the actual algorithm.
- **Verification note:** grep for each symbol across `app` and `tests` returned only its definition.

## Checked, nothing to report
- **Authorization:** member/manage route groups, `scopeBindings` on `{lotteryRun}`, `can:update,project` on the manage group, `authorize('update')` on the download, and the `rankUnits` policy.
- **Double-run prevention:** project-row `lockForUpdate`, then the `activeLotteryRun` check inside the same transaction, which is correct under InnoDB REPEATABLE READ.
- **Reveal pacing:** `PublishLotteryRun` is idempotent on status, `fail()` never overwrites a finished run, the record and download stay hidden while RUNNING, and `LotteryRunResource` does not expose `result`.
- **Invalidation:** COMPLETED-only, public reason, audited.
- **Websocket broadcasts** carry ids only, not the result.
- **Shell safety** of the `glpsol` invocation (`escapeshellarg` on every path).
- **Solution parsing** of wrapped long column names (the regex's `\s+` spans the newline).
- **Open lotteries / no-account links:** not present on main, so key secrecy, enumeration and rate limits could not be assessed.
- **Dropped from the original findings:** "lookups named latest return the oldest audit" as a separate item. It has no effect today and is folded into 03-10.
