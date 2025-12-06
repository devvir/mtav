<!-- Copilot - Pending review -->
# E2E (Playwright + Pest) Overview

This document describes the current end-to-end testing setup for the MTAV project.

**Location**: `tests/Browser` (Playwright tests via the Pest Browser plugin)

## What exists today
- A small, working sample E2E test: `tests/Browser/Invitation/InviteMemberTest.php`.
- E2E tests are executed via the Pest Browser plugin (Playwright integration).
- Docker-based test environment is defined in `.docker/compose.yml` and orchestrated by `.docker/scripts/test.sh` (and the `mtav` wrapper).
- A comprehensive database fixture is available at `tests/Fixtures/universe.sql` and is loaded via the `loadUniverse()` helper.

## Key pieces

- `mtav e2e` (recommended): top-level shortcut that runs the test script with the `e2e` argument.
  - Internally it calls: `.docker/scripts/test.sh e2e`.
- `.docker/scripts/test.sh`: orchestrates starting the testing environment and running the tests. For `e2e` it:
  1. Starts the testing Docker profile (`.docker/compose.yml`, `testing` profile) using `.docker/scripts/up.sh`.
  2. Installs Node deps inside the `assets` container (`npm install --no-save`).
  3. Builds frontend assets inside the `assets` container (`npm run build`).
  4. Removes `public/hot` to avoid injecting Vite dev client.
  5. Runs Pest filtered to `./tests/Browser` (Pest runs the browser tests which connect to Playwright).

- `.docker/compose.yml` (testing profile highlights):
  - `php` container: runs the Laravel app and executes Pest.
  - `assets` container: responsible for node tooling, build and npm install (exposes Vite port in dev but used for builds in testing).
  - `mysql_test` container: ephemeral test DB (uses `tmpfs` for speed) and is used by the test environment.
  - `playwright` container: a Playwright server image (`mcr.microsoft.com/playwright:v1.57.0-noble`) exposing the Playwright run-server on port 5000.

- Pest Browser plugin (vendor/pestphp/pest-plugin-browser): the PHP-side integration that exposes `visit()`, `screenshot()`, `assertSee()`, and other browser helpers. It can use an external Playwright server (the plugin contains `Servers/ExternalPlaywrightServer` and `Servers/PlaywrightNpmServer` implementations).

## Database fixture: `universe.sql`

- Location: `tests/Fixtures/universe.sql` (also documented in `tests/Fixtures/UNIVERSE.md`).
- Purpose: provide a fast, predictable, and comprehensive dataset for tests (predictable IDs, coverage of edge cases like inactive/deleted entities, etc.).
- Loading:
  - `tests/Helpers/fixtures.php` defines `loadUniverse()` which runs `artisan migrate:fresh` and then `DB::unprepared(file_get_contents(base_path('tests/Fixtures/universe.sql')))`.
  - `tests/TestCase.php` calls `loadUniverse()` once per test run (`static::$bootstrapped || loadUniverse();`) so the fixture is loaded a single time for the suite. Tests rely on database transactions / rollbacks for isolation.

## How the Playwright server is used

- The Playwright npm package and server are available in the repo's node configuration (see `package-lock.json`).
- In Docker, a dedicated `playwright` container runs `npx playwright run-server --host 0.0.0.0 --port 5000`.
- The Pest Browser plugin can connect to that external Playwright server (the plugin contains `Servers/ExternalPlaywrightServer` and `Servers/PlaywrightNpmServer` implementations).

## Running the E2E tests (recommended)

1. Ensure the dev environment can be started (Docker available).
2. From the repo root run:

```bash
mtav e2e
```

Notes:
- The `mtav` wrapper calls `.docker/scripts/test.sh e2e` which starts the `testing` profile, installs Node deps, builds, and runs Pest filtered at `./tests/Browser`.
- The script expects containers to be available and will start the testing profile automatically.
- Use `mtav e2e --filter SomeTest` to pass additional Pest filters.

## Where the sample test lives

- `tests/Browser/Invitation/InviteMemberTest.php` — a tiny example that visits `/login`, takes a screenshot, and asserts presence of `email`.

## Screenshots and diffs
- When a screenshot assertion fails the Pest Browser plugin saves screenshots under `tests/Browser/Screenshots` (see plugin support code). The plugin can also show diffs when configured.

## Troubleshooting & tips

- If Playwright is reported as missing/outdated, ensure `npm install playwright` and `npx playwright install` have been run inside the `assets` container; the test script runs `npm install` and `npm run build` automatically before running tests.
- If tests cannot connect to Playwright server, verify the `playwright` container is running and accessible (port 5000 inside container). The `testing` profile in `.docker/compose.yml` defines the Playwright service.
- Database speed: `mysql_test` uses `tmpfs` for faster tests; ensure it's healthy (compose healthchecks are defined).
- Always prefer the `universe.sql` fixture for feature and integration tests (see `.github/copilot-instructions.md` and `tests/Fixtures/UNIVERSE.md`) — it is significantly faster and more predictable than factories for most scenarios.

## Next steps / Suggestions

- Expand `tests/Browser` with more representative user flows (login, invite acceptance, basic CRUD flows) using the existing Pest Browser API.
- Add a short `CONTRIBUTING.md` note pointing contributors to `mtav e2e` and the `universe.sql` fixture.
- Consider caching the built Playwright/browser image for faster CI runs (compose or dedicated image), per project TODOs.

---

Last updated: 6 December 2025
