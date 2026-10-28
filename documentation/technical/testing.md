# Testing & Git Hooks

Testing workflows and automated quality checks. All test commands run inside
the isolated `mtav-testing` Docker environment — see
[docker/README.md](../../docker/README.md) for the infrastructure details.

## 🧪 Running Tests

```bash
./mtav pest [args]       # PHP tests (Pest) — default: Arch, Unit, UseCases suites
./mtav vitest [args]     # Frontend tests (Vitest)
./mtav e2e [args]        # Browser tests (Playwright); --group=critical for the quick subset
./mtav precommit         # Pest (minus 'slow' group) + Vitest in one env cycle
./mtav test              # Everything: Pest + Vitest + E2E
```

Pest/Vitest boot the `mtav-testing` environment; `mtav e2e` boots its own
`mtav-e2e` composition. Each command
installs dependencies, runs, and tears down. Arguments are forwarded to the
underlying runner:

```bash
./mtav pest --filter="runs the lottery"
./mtav pest --stop-on-failure
./mtav pest --testsuite Stress          # opt-in suite: Stress
./mtav vitest resources/js/tests/unit/planEditor.test.ts
./mtav e2e tests/Browser/Features/PlanTest.php
```

### Fast iteration (skip the boot/teardown cycle)

Keep the testing environment up and exec into it directly:

```bash
./mtav compose testing up -d --wait                 # once
docker exec mtav-testing-php-1 php artisan test --testsuite Arch,Unit,UseCases --filter X
docker exec mtav-testing-assets-1 pnpm run test --run
./mtav compose testing down                         # when done
```

The testing environment runs alongside dev (different ports/project), so you
never have to stop your dev session to run tests.

### Suites

- **UseCases** — the business rules, through HTTP (see below).
- **Unit** — the lottery solvers and GLPK internals, on plain data.
- **Arch** — architecture rules.
- **Stress** — slow GLPK scenarios; opt in with `--testsuite Stress`.
- **Browser** — run by `./mtav e2e` (see below).

### Use-case tests

`tests/UseCases/` holds the feature tests that state the business rules, one file per
use case, written from the actor's point of view ("an admin registers a family…").
They start from an empty schema and build exactly the data they need with the
scenario builders in `tests/Helpers/scenario.php` (`aProject()`, `anAdminOf()`,
`aFamily()`, `aMemberOf()`, …) — never fixture IDs. Builders are the only test code
that knows how roles and memberships are stored. Act as a user with
`$this->as($user, $project)`.

Two groups track rules the code does not meet yet:

- `bug` — the app breaks a rule it is meant to follow;
- `new` — a decided requirement that is not built yet.

```bash
./mtav pest --exclude-group=bug --exclude-group=new   # what should be green
./mtav pest --group=bug --group=new                   # the open list
```

### Browser tests

`tests/Browser/` drives the real app in Chromium (Pest's browser plugin on Playwright).
The app runs in-process, so tests mix browser steps with database checks.

- `Critical/` (group `critical`): every page of the three areas renders without
  JavaScript errors, and a cooperative goes from sign-up to its lottery. The quick
  subset: `./mtav e2e --group=critical`.
- `Features/`: one file per area of the app (accounts, projects, setup, people,
  events, files, the lottery, the plan, guided help), plus phones, Spanish and
  accessibility (axe). Edge cases live here: refusals, limits, validation, the last admin.

Fields are found by their `name`; buttons by their text, icon buttons by
`[aria-label="…"]`. Helpers in `tests/Helpers/browser.php`: `inDialog()` and `button()`
(a button by its text, never a heading saying the same), `attachFile()` (the browser
refuses local paths), and the links that would arrive by email. Assertions are retried
until the timeout (20 s); clicks and key presses run once.

### Notes

- Every test starts from an empty schema (migrated once per process) and runs inside a
  rolled-back transaction (`RefreshDatabase`), which still fires after-commit callbacks.
- Lottery unit tests audit in memory (`fakeAudits()`, `tests/Helpers/lottery.php`).
- The testing database lives in RAM (tmpfs) and is discarded on teardown.
- Don't run two test invocations concurrently — they share the
  `mtav-testing` compose project.

## 🔗 Git Hooks

### Pre-commit (`.husky/pre-commit`)

Runs core tests scoped to the **staged** files against the already-running
testing environment (docs-only commits are instant; PHP-only commits skip
Vitest and vice versa):

- PHP: Arch + Unit suites minus the `slow` group (~50s)
- Frontend: full Vitest run (parallel with the PHP run)

If the testing environment is not up, the hook **skips with a warning**
instead of paying the multi-minute boot cost — so keep it up while
developing (`./mtav compose testing up -d --wait`). Run `mtav precommit`
for the fuller check before pushing.

### Pre-push (`.husky/pre-push`)

Runs `php artisan insights` (code quality analysis).

### Bypassing hooks

```bash
git commit --no-verify -m "Emergency fix"   # ⚠️ use only when necessary
git push --no-verify
```
