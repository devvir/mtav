# Testing

Every rule the app keeps has a test that states it, and every fix comes with a test that
would have caught what it fixes. The tests run in their own Docker environments
([development.md](development.md)), never against the dev database.

## Running them

```sh
./mtav pest                              # PHP: Arch, Unit, UseCases
./mtav pest --parallel                   # the same, one process per core, as the pipeline runs it
./mtav pest --filter="runs the lottery"  # by name
./mtav pest --testsuite Stress           # the slow GLPK scenarios (opt in; solver.yml runs them in GitHub)
./mtav vitest                            # frontend units
./mtav e2e                               # the browser suite
./mtav e2e --group=critical              # its quick part
./mtav e2e --filter='LotteryTest|PlanTest'
./mtav e2e --shard=1/3                   # a third of its files, as each of the pipeline's three parts
./mtav precommit                         # Pest without the slow group, and Vitest
./mtav test --pest --filter=lottery --vitest plan   # all three, each with its own arguments
```

`./mtav pest` and `./mtav vitest` start the testing environment when it is down and stop it
again after; one found running stays running. To iterate without the start each time:

```sh
./mtav compose testing up -d --wait
./mtav pest --filter X                   # or, without the dependency check:
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
| Unit | `tests/Unit/` | the lottery's solvers and GLPK, on plain data; where the plan puts a unit added without a place |
| Arch | `tests/Arch/` | rules about the code: every language says what English says; no debugging left; models free of HTTP, solvers free of models; the release built from pinned parts; every route belongs to a part of the app |
| Stress | `tests/Stress/` | GLPK on hard instances (timeouts, binary search against the direct method) |
| Browser | `tests/Browser/` | the real app in Chromium |
| Vitest | `resources/js/tests/` | frontend logic: the plan editor and its saving, zoom, idle, offline, rankings |

**Use cases** build exactly the data they need with the scenario builders
(`tests/Helpers/scenario.php`: `aProject()`, `anAdminOf()`, `aFamily()`, `aMemberOf()`,
`aUnitType()`, `aUnit()`, `anEvent()`, `aLotteryInProgress()`…), never fixture ids, and
act with `$this->as($user, $project)`. Each test starts from an empty schema, inside a
transaction rolled back after it (after-commit callbacks still run).

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
Assertions retry until 20 seconds pass; clicks and keys run once. A test waits for what
stays (the record in its list, the status on the page, the row in the database), not for a
toast, which is gone in seconds, and its selectors match one element however far the page
has loaded (a unit's name is in its list and on the plan).

A test never sleeps a fixed time. What shows, it asserts (the assertion waits); what a timer
does (a picture changing, a retry, a toast leaving, a tour starting), it runs ahead on the
page's clock; and before saying something did not happen, it waits for the sign that it would
have by then: the page drawn, the server's answer back, the timer run. Helpers in
`tests/Helpers/browser.php`:

| Helper | For |
|---|---|
| `inDialog()`, `button()` | a button inside the open dialog; a button by its text, never a heading |
| `attachFile()` | files to upload (the browser refuses local paths) |
| `openHelp($page, $topic)` | a (?) opened, waiting until its popover stands still |
| `standsStill($page, $selector)` | until what moves in (a tour's card, its spotlight) keeps its place |
| `settled($page)` | what the page had queued done and drawn: after a click, before checking nothing came of it |
| `waitUntil($page, $condition)` | until a condition on the page holds, for what Pest checks only once (`assertScript`, `assertPathIs`) |
| `watchRequests($page)`, `answered($page, $path)` | the page's requests followed; then until they are answered (one to that path, when given) and drawn |
| `fakeClock($page)`, `runClock($page, $ms)` | the page's clock taken over by Playwright's, time still flowing; then run ahead, its timers fired (refresh after `fakeClock()` for the timers a page sets as it loads) |
| `pickDay($page, $field, $day)` | a day picked on a date field's calendar, as a person does |
| `eventually($page, $check)` | a check on the database, repeated while the app answers the browser, for a save nothing on the page confirms |
| `sameTabTo($page, $url)` | the same tab to another address, however long it loads |
| `assertAccessible($page)` | axe's serious and critical findings, none allowed |

A page asked for at another size (`visit($url)->on()->mobile()`) opens with its first call,
and each call made on what `on()` returns opens another: a helper is given what that first call
returned (`->assertPresent(…)`), never the pending page.

Tours never start by themselves under test (the browser is automated); a test that wants
them sets `localStorage['mtav.tours'] = 'on'`.

## Before committing

**Locally and in CI.** The whole browser suite is for GitHub's pipeline, which runs it on every
push to any branch (the repository is public: its minutes are free), split in three parts that
run at once; Pest runs there in parallel, each process with its own database. Locally, `--group=critical`
and the tests of what changed are enough; a `task/...` branch runs the whole pipeline without
deploying anything, while work moves on.

The pre-commit hook runs Arch and Unit (without the slow group) when PHP is staged, and
Vitest when frontend code is, against a testing environment already up (it skips, with a
warning, when none is). The pipeline runs everything on every push ([delivery.md](delivery.md)).
