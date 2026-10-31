# Testing

Every rule the app keeps has a test that states it, and every fix comes with a test that
would have caught what it fixes. The tests run in their own Docker environments
([development.md](development.md)), never against the dev database.

## Running them

```sh
./mtav pest                              # PHP: Arch, Unit, UseCases
./mtav pest --filter="runs the lottery"  # by name
./mtav pest --testsuite Stress           # the slow GLPK scenarios (opt in)
./mtav vitest                            # frontend units
./mtav e2e                               # the browser suite
./mtav e2e --group=critical              # its quick part
./mtav e2e --filter='LotteryTest|PlanTest'
./mtav precommit                         # Pest without the slow group, and Vitest
```

To iterate without starting and stopping the environment each time:

```sh
./mtav compose testing up -d --wait
docker exec mtav-testing-php-1 php artisan test --testsuite Arch,Unit,UseCases --filter X
./mtav compose testing down
```

Two runs of the same kind never at once: Pest and Vitest share `mtav-testing`, browser
runs `mtav-e2e`. The browser run reads the app's PHP from the working tree as it goes:
editing the app while it runs makes its results meaningless.

## The suites

| Suite | Where | What |
|---|---|---|
| UseCases | `tests/UseCases/<area>/` | the business rules, through HTTP, from the actor's side ("an admin runs the lottery…") |
| Unit | `tests/Unit/` | the lottery's solvers and GLPK, on plain data |
| Arch | `tests/Arch/` | rules about the code: every language says what English says; no debugging left; models free of HTTP, solvers free of models |
| Stress | `tests/Stress/` | GLPK on hard instances (timeouts, binary search against the direct method) |
| Browser | `tests/Browser/` | the real app in Chromium |
| Vitest | `resources/js/tests/` | frontend logic: the plan editor, zoom, idle, offline |

**Use cases** build exactly the data they need with the scenario builders
(`tests/Helpers/scenario.php`: `aProject()`, `anAdminOf()`, `aFamily()`, `aMemberOf()`,
`aUnitType()`, `aUnit()`, `anEvent()`, `aLotteryInProgress()`…), never fixture ids, and
act with `$this->as($user, $project)`. Each test starts from an empty schema, inside a
transaction rolled back after it (after-commit callbacks still run). Two groups mark
rules not met yet: `bug` (broken) and `new` (decided, not built).

**The solver** has its own checks, kept from the first version word for word
(`tests/Unit/Lottery/Glpk/GlpkSolverTest.php`): exact picks, the worst-off family first,
ties, the same answer on every run, larger cases, leftovers, a missing `glpsol`.

**Browser tests** run the app in-process, so a test mixes browser steps with database
checks:

- `Critical/`: every page of every area renders without errors; a cooperative from
  sign-up to its lottery; the tutor's journey on the first version's flows.
- `Features/`: one file per area (accounts, projects, setup, people, events, files, the
  plan, the lottery, the home page, notifications, live updates, guided help, the
  console…), plus phones, Spanish and accessibility.

Fields are found by `name`, buttons by their text, icon buttons by `aria-label`.
Assertions retry until 20 seconds pass; clicks and keys run once. Helpers in
`tests/Helpers/browser.php`:

| Helper | For |
|---|---|
| `inDialog()`, `button()` | a button inside the open dialog; a button by its text, never a heading |
| `attachFile()` | files to upload (the browser refuses local paths) |
| `openHelp($page, $topic)` | a (?) opened, waiting until its popover stands still |
| `pickDay($page, $field, $day)` | a day picked on a date field's calendar, as a person does |
| `assertAccessible($page)` | axe's serious and critical findings, none allowed |

Tours never start by themselves under test (the browser is automated); a test that wants
them sets `localStorage['mtav.tours'] = 'on'`.

## Before committing

**Locally and in CI.** The whole browser suite is for GitHub's pipeline, which runs it on every
push to any branch (the repository is public: its minutes are free). Locally, `--group=critical`
and the tests of what changed are enough; a `task/...` branch runs the whole pipeline without
deploying anything, while work moves on.

The pre-commit hook runs the tests that the staged files touch, against a testing
environment already up (it skips, with a warning, when none is). The pipeline runs
everything on every push ([delivery.md](delivery.md)).
