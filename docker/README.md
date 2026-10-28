# MTAV Docker Infrastructure

Compositional Docker setup: small **generic services** composed into isolated
**environments**, driven by the single-file **`mtav` CLI** at the repo root.
This document is the canonical reference — the overview gets you productive,
the specification explains every mechanism, decision, and edge case.

---

# Part 1 — Overview

## Environments

Each environment is an isolated Docker Compose project (own network, volumes,
containers). Dev and testing are designed to run **simultaneously**.

| Environment | Project | Purpose | Code | Assets | Database |
|---|---|---|---|---|---|
| `dev` | `mtav-dev` | daily development | live (bind mount) | **HMR**, always | persistent volume, migrate on every up |
| `testing` | `mtav-testing` | Pest / Vitest | live (bind mount) | none needed | **tmpfs** (RAM, discarded) |
| `e2e` | `mtav-e2e` | Browser journey tests (Playwright) | live (bind mount) | **built** per run | **tmpfs** (RAM, discarded) |

Releases are built and deployed apart from these environments: see
`documentation/technical/delivery.md` (`mtav release` builds the release image locally).

## Commands

```sh
# Environments
mtav dev                 # start dev (alias: mtav up) — live code + HMR
mtav up [env]            # generic form (dev|testing)

# Lifecycle
mtav stop [env]          # stop containers (kept for quick restart)
mtav down [env]          # stop and remove containers (volumes survive)
mtav restart             # down + up (dev)
mtav fresh               # THE db-reset: dev down + volumes wiped + rebuild + migrate + seed
mtav update              # rebuild images, composer/pnpm install, migrate (dev)
mtav rebuild [service]   # rebuild image(s) --no-cache (dev)
mtav status [env]        # containers + URLs
mtav logs [env] [svc]    # follow logs

# Testing (pest/vitest boot mtav-testing; e2e boots mtav-e2e; run → tear down)
mtav test                # everything: Pest + Vitest + E2E
mtav pest [args]         # PHP tests (default --testsuite Arch,Unit,Feature)
mtav vitest [args]       # Vue tests
mtav e2e [args]          # Browser journeys (see tests/Browser/README.md)
mtav precommit           # Pest (minus 'slow' group) + Vitest in ONE env cycle

# Shortcuts (run inside dev containers)
mtav artisan <cmd>       # php artisan …
mtav tinker              # artisan tinker
mtav composer <cmd>
mtav pnpm <cmd>          # package manager (npm is gone; 'mtav npm' forwards + warns)
mtav shell [container]   # sh into a container (default php); -c 'cmd' for one-offs

# Escape hatch — raw docker compose against any environment
mtav compose <env> <any docker compose args>
#   e.g.  mtav compose testing up -d --wait     (keep testing env up for fast iteration)
#         mtav compose dev logs -f mysql
```

## Gotchas

- **Never delete `public/hot` while dev is running.** Vite only recreates it
  on restart; without it dev silently falls back to built (stale) assets.
  You never need to delete it: testing and e2e ignore it entirely (see
  [Vite: HMR vs built assets](#vite-hmr-vs-built-assets)).
- **Ports are env vars, never hardcoded.** Committed defaults live in
  `docker/<env>/.env`; personal/server overrides go in `docker/<env>/.env.local`
  (gitignored). Empty value ⇒ random host port. All defaults are >1024.
- **Playwright versions are pinned together.** The e2e image tag
  (`docker/services/e2e/Dockerfile`, `mcr.microsoft.com/playwright:v<X>-noble`)
  ships that exact version's browsers, so the `playwright` devDependency in
  package.json MUST match it (currently 1.64.1). The pest
  browser plugin also enforces a minimum client version — check
  `PlaywrightNpmServer::PLAYWRIGHT_VERSION` in the plugin when bumping.
- **pnpm is strict** — imports must be declared in package.json (npm's flat
  hoisting hid this). A build error like `Failed to resolve import "x"`
  usually means a transitive dep is used directly: `mtav pnpm add x`.
  Dependency postinstall scripts must be allow-listed in
  `allowBuilds` in `pnpm-workspace.yaml` (currently: esbuild).
- **Don't run two same-kind test invocations concurrently** — pest/vitest
  share the `mtav-testing` project and e2e the `mtav-e2e` project. Dev + one
  of each test kind simultaneously is fine (that's the point).
- **`mtav up` never wipes data.** It runs a plain `migrate` every time.
  `mtav fresh` is the one and only command that destroys the dev database.
- **First run is special**: `mtav dev` on a pristine clone creates `.env`
  from `.env.template` (unique APP_KEY), builds everything, migrates, seeds
  (witness file: `docker/.first-run`, gitignored).
- If the whole Pest suite ever fails at `loadUniverse()`, check for a stale
  `bootstrap/cache/config.php` in the bind mount (`mtav artisan optimize:clear`).

---

# Part 2 — Technical Specification

## Directory layout

```
docker/
├── README.md              # this file
├── .first-run             # gitignored witness: first-time dev setup completed
│
├── services/              # generic, environment-agnostic building blocks
│   ├── php/               # Dockerfile (multi-stage), base.yml, compose.yml, php.ini
│   ├── nginx/             # compose.yml, dev.conf
│   ├── mysql/             # base.yml, compose.yml (persistent variant)
│   ├── assets/            # compose.yml (Vite dev), dev.Dockerfile
│   ├── reverb/            # compose.yml (php base + reverb command)
│   ├── mailpit/           # compose.yml
│   └── e2e/               # Dockerfile (playwright image + PHP), compose.yml
│
├── dev/                   # composition + committed .env  (+ optional .env.local)
├── testing/               #   〃
└── e2e/                   #   〃
```

## Design principles

1. **Services are generic.** A service compose file knows nothing about
   environments: no project names, no fixed host ports, no profiles.
   Everything variable is an interpolated env var with a sane default
   (`"${NGINX_PORT:-}:80"` — empty publishes a random port). A service that
   can't express a variant through env vars ships a `base.yml` that variants
   `extends` (mysql: tmpfs vs volume).

2. **Compositions own identity.** Each `docker/<env>/compose.yml` declares
   `name: mtav-<env>`, which gives it its own network, volume namespace, and
   container names (`mtav-<env>-<service>-1`) — full isolation with zero
   `-p`/`COMPOSE_PROJECT_NAME` juggling. Services used as-is are pulled in
   with `include:`; services needing composition-level wiring (extra mounts,
   `depends_on`, tmpfs, profiles) are declared inline with `extends:`.
   Compose rule to remember: `include` forbids overriding, `extends` doesn't
   carry `depends_on` — so wiring always lives in the composition.

3. **Hostnames are plain service names.** Within a composition, the app
   reaches `mysql`, `reverb`, `mailpit` — no `mysql_test`-style renaming,
   because isolation comes from the project, not the service name.

4. **Env layering.** Every `mtav` invocation runs:

   ```
   docker compose -f docker/<env>/compose.yml \
     --env-file .env \                  # root: app config, PUID/PGID, APP_KEY
     --env-file docker/<env>/.env \     # committed: ports, APP_ENV, env flags
     [--env-file docker/<env>/.env.local]   # optional personal overrides, gitignored
   ```

   Later files win. These files feed compose *interpolation*; the Laravel app
   additionally reads the root `.env` at runtime (bind mount). Container `environment:` entries (e.g.
   `DB_HOST`, `APP_ENV`, `VITE_FORCE_BUILD`) override the app's `.env` —
   that's how one shared `.env` serves every environment.

   Changing a port in `.env`/`.env.local` and re-running `mtav <env>` is the
   supported way to apply it: compose recreates exactly the affected
   containers.

## Services

### php — `docker/services/php/Dockerfile` (multi-stage, context = repo root)

| Stage | Adds | Used by |
|---|---|---|
| `base` | php:8.5.12-fpm-alpine, extensions (pdo_mysql, gd, intl, zip, sockets, bcmath, exif, pcntl), ffmpeg, glpk, composer | — |
| `dev` | xdebug (off by default, `XDEBUG_MODE=coverage` to enable), node + standalone pnpm, `hostuser` mapped to host UID/GID (`PUID`/`PGID` build args) | dev/testing php, reverb |

Compose files: `base.yml` (build def + `APP_ENV`/`DB_HOST`/`VITE_FORCE_BUILD`
env passthrough — extended by everything PHP), `compose.yml` (dev variant:
bind mounts code + `php.ini`).

The old ghcr.io base-image dependency is gone: the `base` stage builds
locally (minutes, once — layer-cached afterwards, shared across all targets).

### nginx

- **dev** (`compose.yml`): stock `nginx:1.30.6-alpine`, serves the bind-mounted
  `public/`, fastcgi to `php:9000` (`dev.conf`: generous timeouts/buffers).

### mysql

`base.yml`: mariadb:12.3 (LTS), credentials from `DB_*` vars (root password
`DB_ROOT_PASSWORD`, default `root` — phpunit.xml expects root/root),
healthcheck every 3s. `compose.yml` extends it with the persistent
`mysql-data` volume and a localhost-only port binding. The testing
composition extends `base.yml` directly and mounts `tmpfs: /var/lib/mysql`
instead — the DB lives in RAM and evaporates on down, which is both the
speed trick and the reason tests never need cleanup.

### assets

- **dev** (`compose.yml` + `dev.Dockerfile`): node:26.12.0-alpine + standalone
  pnpm binary, `CMD pnpm install && pnpm run dev --host 0.0.0.0` — Vite with
  HMR on `VITE_PORT`. Chokidar polling (inotify is unreliable across the
  bind mount).

pnpm specifics: the standalone binary (self-contained, bundles its own node)
is pinned by `PNPM_VERSION` in the Dockerfiles and must match
`packageManager` in package.json. The store lands in `<repo>/.pnpm-store`
(gitignored): pnpm can't hardlink across filesystems (bind mount ↔ volume),
so it keeps the store project-local — which conveniently survives container
recreation.

### reverb

`extends` php `base.yml` with `command: php artisan reverb:start` and the
dev bind mounts. Reverb touches the database at boot (unlike php-fpm, which
connects per-request), so every composition wires
`depends_on: mysql: condition: service_healthy` — without it, reverb
crash-loops once at cold start before the restart policy saves it.

### mailpit

SMTP sink + web UI, present in dev so no environment
can ever send real mail.

### e2e

The E2E runner: `mcr.microsoft.com/playwright:v<X>-noble` (node + browsers +
system deps) with PHP 8.5 (ondrej PPA), composer, pnpm, GLPK (real lottery
solver), and ffmpeg added on top. Everything the browser journeys need lives
in this one container because the **official** pest-plugin-browser assumes a
single host: it serves the Laravel app **in-process** (an Amp socket server
inside the pest process — which is why `actingAs()`, event listeners, and
Eloquent all work mid-journey) and spawns `node_modules/.bin/playwright
run-server` on localhost. The container idles; the `mtav e2e` flow execs
build/test steps into it. See `tests/Browser/README.md` for the suite.

## Compositions

### dev (`mtav-dev`)

Includes php, nginx, mysql, assets, mailpit; wires reverb (`depends_on`
mysql). Ports: app **8000**, Vite **5173**, mysql **3307** (localhost only),
mailpit **1025/8025**, reverb **8080**.

### testing (`mtav-testing`)

Includes php and nginx; wires tmpfs-mysql and assets with
`command: tail -f /dev/null` — in testing the assets container is a **node
toolbox**, not a dev server (Vitest is headless), and an idle command means
exec'd `pnpm install/test` never race a boot-time install. Ports: app
**8001**, mysql **3308**, Vite unpublished. No websocket server: the
use-case tests fake broadcast events (the browser suite runs a real one). Sets
`APP_ENV=testing` and `VITE_FORCE_BUILD=true`.

DB credentials in tests: the container env pins `DB_HOST=mysql`; phpunit.xml
supplies `DB_USERNAME/DB_PASSWORD=root/root` (its `DB_HOST=mysql_test`
default is overridden by the real container env — PHPUnit `<env>` entries
only apply when the variable isn't already set).

### e2e (`mtav-e2e`)

Just the all-in-one e2e runner + a tmpfs mysql — no nginx (the app is served
in-process), no published ports, fully parallel to dev and testing. The
`mtav e2e` flow: up → composer/pnpm install → `pnpm run build` → start
Reverb **inside the same container** (so the browser's `ws://localhost:8080`
is real) → `php artisan test --testsuite Browser` → remove `public/build` →
down. What the suite covers: `documentation/technical/testing.md`.

## Vite: HMR vs built assets

laravel-vite-plugin writes `public/hot` (containing the dev-server URL)
while `vite dev` runs and removes it on clean shutdown; Laravel's `@vite`
serves HMR URLs iff that file exists, else it reads
`public/build/manifest.json`. Because dev/testing/e2e share one bind
mount, that single file used to flip environments unpredictably (stale hot
after a crash → a test run serves dead HMR URLs; a test run deleting hot →
dev silently degrades). The rules are now deterministic:

- **dev**: always HMR. Vite rewrites `public/hot` on every container start,
  and nothing else is allowed to delete it. A leftover `public/build/` is
  irrelevant (hot wins).
- **testing / e2e**: always built. Their php containers set
  `VITE_FORCE_BUILD=true`; `AppServiceProvider::configureVite()` then points
  `Vite::useHotFile()` at a path that never exists, so the hot check can
  never pass — regardless of what dev leaves in the mount. This is container
  env (not `.env`), so it survives `config:cache` (`env()` still reads real
  process env).

Verified behavior: with *both* `public/hot` and `public/build/` present,
dev serves `localhost:5173/@vite/...` URLs and testing serves
`/build/assets/...` — simultaneously, from the same working tree.

## The `mtav` CLI

Single bash script, no external helpers. Internals: `compose <env> …` builds
the `-f`/`--env-file` invocation above; `cexec` adds `-T` when stdin isn't a
TTY (CI, pipes); `env_port` resolves a var through the same layering (used
by `status` URLs); `env_setup` creates `.env` from `.env.template` on first
contact (unique APP_KEY via openssl, host UID/GID) and afterwards reconciles
missing keys non-interactively + keeps PUID/PGID synced.

Per command — motivation and edge cases:

- **`up [env]` / `dev`** — the idempotent "make it so": start whatever isn't
  running (`--wait` on healthchecks, 120s), then plain `migrate`. Never
  destructive, safe to run reflexively. First dev run detects the missing
  `docker/.first-run` witness and does the whole onboarding (build, composer
  install, migrate, seed, storage:link) — a new developer types `mtav up`
  and gets a working app.
- **`fresh`** — the only DB-destroying command, and explicitly so:
  `down --volumes` + witness removal + re-run of the first-run path (build,
  migrate, seed). Everything else in the CLI treats data as sacred.
- **`update`** — after pulling: rebuild images (cached), composer + pnpm
  install, migrate.
- **`release`** — builds the release image (`deploy/Dockerfile`) as
  `mtav-release:local`, exactly as the pipeline does.
- **test commands** — `pest`/`vitest` boot `mtav-testing`, `composer install`
  (`--no-scripts`, cached vendor makes it fast), install node deps when
  Vitest is involved, run, tear down. `pest` validates `--testsuite` values
  (Arch|Unit|Feature|Stress, default Arch,Unit,Feature — Browser and Stress
  are opt-in). `e2e` drives the separate `mtav-e2e` composition (see above);
  it builds assets and afterwards removes `public/build` so the host isn't
  left polluted — but never touches `public/hot`. `test` = all three.
  `precommit` is the tight loop: Pest minus the `slow` group + Vitest in one
  env cycle. For sub-minute iteration, skip the cycle entirely:
  `mtav compose testing up -d --wait` once, then
  `docker exec mtav-testing-php-1 php artisan test …` repeatedly (the husky
  pre-commit hook does exactly this and warn-skips when the env is down).
- **`compose <env> …`** — the escape hatch. The CLI wraps compose, never
  hides it; anything not covered by a command is one `mtav compose` away
  with the correct project and env layering guaranteed.
- **`npm`** — forwards to pnpm with a warning (muscle-memory shim).
