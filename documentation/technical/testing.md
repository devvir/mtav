# Testing & Git Hooks

Testing workflows and automated quality checks. All test commands run inside
the isolated `mtav-testing` Docker environment — see
[docker/README.md](../../docker/README.md) for the infrastructure details.

## 🧪 Running Tests

```bash
./mtav pest [args]       # PHP tests (Pest) — default: Arch, Unit, Feature suites
./mtav vitest [args]     # Vue tests (Vitest)
./mtav e2e [args]        # Browser tests (Playwright) — currently being stabilized
./mtav precommit         # Pest (minus 'slow' group) + Vitest in one env cycle
./mtav test              # Everything: Pest + Vitest + E2E
```

Each command boots the testing environment, installs dependencies, runs, and
tears down. Arguments are forwarded to the underlying runner:

```bash
./mtav pest --filter="AdminControllerCrudTest"
./mtav pest --stop-on-failure
./mtav pest --testsuite Stress          # opt-in suites: Stress, Browser
./mtav vitest resources/js/tests/unit/composables/useAuth.test.ts
```

### Fast iteration (skip the boot/teardown cycle)

Keep the testing environment up and exec into it directly:

```bash
./mtav compose testing up -d --wait                 # once
docker exec mtav-testing-php-1 php artisan test --testsuite Arch,Unit,Feature --filter X
docker exec mtav-testing-assets-1 pnpm run test --run
./mtav compose testing down                         # when done
```

The testing environment runs alongside dev (different ports/project), so you
never have to stop your dev session to run tests.

### Notes

- Tests use the `tests/Fixtures/universe.sql` fixture (see
  `tests/Fixtures/UNIVERSE.md`) — loaded once per process, each test wrapped
  in a rolled-back transaction.
- The testing database lives in RAM (tmpfs) and is discarded on teardown.
- Don't run two test invocations concurrently — they share the
  `mtav-testing` compose project.

## 🔗 Git Hooks

### Pre-commit (`.husky/pre-commit`)

Runs core tests scoped to the **staged** files against the already-running
testing environment (docs-only commits are instant; PHP-only commits skip
Vitest and vice versa):

- PHP: Arch + Unit suites minus the `slow` group (~50s)
- Vue: full Vitest run (parallel with the PHP run)

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

## 📋 Important Notes

### InertiaUI version synchronization

Keep the InertiaUI packages in sync to avoid runtime issues — update both
to the same version:

```bash
./mtav composer update inertiaui/modal
./mtav pnpm update @inertiaui/modal-vue
```
