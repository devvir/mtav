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
| `staging` | `mtav-staging` | snapshot check before baking images | current at spin time | **built** at spin time | persistent volume, migrate (+seed when fresh) |
| `prod` | `mtav-prod` | frozen release preview / deployment | **baked into images** | **baked into images** | persistent volume, one-shot migrations |

The intent ladder: **dev** reflects every change in real time → **staging**
freezes a snapshot "as it is right now" to eyeball before committing to it →
**prod** runs only what was explicitly baked with `mtav prod build`.

## Commands

```sh
# Environments
mtav dev                 # start dev (alias: mtav up) — live code + HMR
mtav staging             # (re-)spin staging — current code, built assets, no HMR
mtav prod                # start prod — frozen pre-built images (default action: up)
mtav prod build          # bake new prod images from current code
mtav up [env]            # generic form (dev|testing|staging|prod)

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
  You never need to delete it: testing/staging/prod ignore it entirely (see
  [Vite: HMR vs built assets](#vite-hmr-vs-built-assets)).
- **Staging replaces dev** — same ports by design. Stop dev first. After
  staging, run `mtav artisan optimize:clear && rm -rf public/build` before
  returning to dev (staging caches config/routes/views into the shared bind
  mount; the command prints this reminder).
- **Ports are env vars, never hardcoded.** Committed defaults live in
  `docker/<env>/.env`; personal/server overrides go in `docker/<env>/.env.local`
  (gitignored). Empty value ⇒ random host port. All defaults are >1024
  (third-party hosts often forbid Docker binding low ports; prod is expected
  to sit behind a host-level nginx that terminates SSL and proxies to
  whatever port prod exposes).
- **Playwright versions are pinned together.** The e2e image tag
  (`docker/services/e2e/Dockerfile`, `mcr.microsoft.com/playwright:v<X>-noble`)
  ships that exact version's browsers, so the `playwright` / `@playwright/test`
  devDependencies in package.json MUST match it (currently 1.61.1). The pest
  browser plugin also enforces a minimum client version — check
  `PlaywrightNpmServer::PLAYWRIGHT_VERSION` in the plugin when bumping.
- **pnpm is strict** — imports must be declared in package.json (npm's flat
  hoisting hid this). A build error like `Failed to resolve import "x"`
  usually means a transitive dep is used directly: `mtav pnpm add x`.
  Dependency postinstall scripts must be allow-listed in
  `pnpm.onlyBuiltDependencies` (currently: esbuild, vue-demi).
- **Don't run two same-kind test invocations concurrently** — pest/vitest
  share the `mtav-testing` project and e2e the `mtav-e2e` project. Dev + one
  of each test kind simultaneously is fine (that's the point).
- **`mtav up` never wipes data.** It runs a plain `migrate` every time.
  `mtav fresh` is the one and only command that destroys the dev database.
- **First run is special**: `mtav dev` on a pristine clone creates `.env`
  from `.env.template` (unique APP_KEY), builds everything, migrates, seeds
  (witness file: `docker/.first-run`, gitignored).
- If the whole Pest suite ever fails at `loadUniverse()`, check for a stale
  `bootstrap/cache/config.php` in the bind mount (see staging gotcha above).

---

# Part 2 — Technical Specification

## Directory layout

```
docker/
├── README.md              # this file
├── .first-run             # gitignored witness: first-time dev setup completed
│
├── services/              # generic, environment-agnostic building blocks
│   ├── php/               # Dockerfile (multi-stage), base.yml, compose.yml,
│   │                      #   prod.yml, php.ini (dev), prod.ini
│   ├── nginx/             # compose.yml (dev), prod.yml, prod.Dockerfile,
│   │                      #   dev.conf, prod.conf
│   ├── mysql/             # base.yml, compose.yml (persistent variant)
│   ├── assets/            # compose.yml (Vite dev), prod.yml, dev.Dockerfile,
│   │                      #   prod.Dockerfile, prod.conf
│   ├── reverb/            # compose.yml (php base + reverb command)
│   ├── queue/             # compose.yml (prod workers), supervisord.conf
│   ├── mailhog/           # compose.yml
│   └── e2e/               # Dockerfile (playwright image + PHP), compose.yml
│
├── dev/                   # composition + committed .env  (+ optional .env.local)
├── testing/               #   〃
├── e2e/                   #   〃
├── staging/               #   〃
└── prod/                  #   〃
```

## Design principles

1. **Services are generic.** A service compose file knows nothing about
   environments: no project names, no fixed host ports, no profiles.
   Everything variable is an interpolated env var with a sane default
   (`"${NGINX_PORT:-}:80"` — empty publishes a random port). A service that
   can't express a variant through env vars ships a `base.yml` that variants
   `extends` (mysql: tmpfs vs volume; php: dev vs prod image).

2. **Compositions own identity.** Each `docker/<env>/compose.yml` declares
   `name: mtav-<env>`, which gives it its own network, volume namespace, and
   container names (`mtav-<env>-<service>-1`) — full isolation with zero
   `-p`/`COMPOSE_PROJECT_NAME` juggling. Services used as-is are pulled in
   with `include:`; services needing composition-level wiring (extra mounts,
   `depends_on`, tmpfs, profiles) are declared inline with `extends:`.
   Compose rule to remember: `include` forbids overriding, `extends` doesn't
   carry `depends_on` — so wiring always lives in the composition.

3. **Hostnames are plain service names.** Within a composition, the app
   reaches `mysql`, `reverb`, `mailhog` — no `mysql_test`-style renaming,
   because isolation comes from the project, not the service name.

4. **Env layering.** Every `mtav` invocation runs:

   ```
   docker compose -f docker/<env>/compose.yml \
     --env-file .env \                  # root: app config, PUID/PGID, APP_KEY
     --env-file docker/<env>/.env \     # committed: ports, APP_ENV, env flags
     [--env-file docker/<env>/.env.local]   # optional personal overrides, gitignored
   ```

   Later files win. These files feed compose *interpolation*; the Laravel app
   additionally reads the root `.env` at runtime (bind mount) or via
   `env_file:` (prod images). Container `environment:` entries (e.g.
   `DB_HOST`, `APP_ENV`, `VITE_FORCE_BUILD`) override the app's `.env` —
   that's how one shared `.env` serves four environments.

   Changing a port in `.env`/`.env.local` and re-running `mtav <env>` is the
   supported way to apply it: compose recreates exactly the affected
   containers.

## Services

### php — `docker/services/php/Dockerfile` (multi-stage, context = repo root)

| Stage | Adds | Used by |
|---|---|---|
| `base` | php:8.4-fpm-alpine, extensions (pdo_mysql, gd, intl, zip, sockets, bcmath, exif, pcntl), ffmpeg, glpk, composer | — |
| `dev` | xdebug (off by default, `XDEBUG_MODE=coverage` to enable), node + standalone pnpm, `hostuser` mapped to host UID/GID (`PUID`/`PGID` build args) | dev/testing/staging php, reverb |
| `prod` | `prod.ini` (opcache, no display_errors), `composer install --no-dev`, app code baked in, storage skeleton, `public/build/manifest.json` symlink → shared volume, `CMD php artisan optimize && php-fpm` | prod php, migrations, reverb |
| `queue` | supervisord + 3 `queue:work` workers | prod queue |

Compose files: `base.yml` (build def + `APP_ENV`/`DB_HOST`/`VITE_FORCE_BUILD`
env passthrough — extended by everything PHP), `compose.yml` (dev variant:
bind mounts code + `php.ini`), `prod.yml` (target `prod`, `env_file` root
`.env`, `app-storage` + `vite-manifest` volumes — declared by the consuming
composition, so `prod.yml` is not loadable standalone; it exists only to be
extended).

The old ghcr.io base-image dependency is gone: the `base` stage builds
locally (minutes, once — layer-cached afterwards, shared across all targets).

### nginx

- **dev** (`compose.yml`): stock `nginx:1.26-alpine`, serves the bind-mounted
  `public/`, fastcgi to `php:9000` (`dev.conf`: generous timeouts/buffers).
- **prod** (`prod.yml` + `prod.Dockerfile`): pure proxy, no filesystem —
  static asset paths proxied to the `assets` service, everything else
  fastcgi to `php:9000` (`prod.conf`: security headers, gzip).

### mysql

`base.yml`: mariadb:12, credentials from `DB_*` vars (root password
`DB_ROOT_PASSWORD`, default `root` — phpunit.xml expects root/root),
healthcheck every 3s. `compose.yml` extends it with the persistent
`mysql-data` volume and a localhost-only port binding. The testing
composition extends `base.yml` directly and mounts `tmpfs: /var/lib/mysql`
instead — the DB lives in RAM and evaporates on down, which is both the
speed trick and the reason tests never need cleanup.

### assets

- **dev** (`compose.yml` + `dev.Dockerfile`): node:22-alpine + standalone
  pnpm binary, `CMD pnpm install && pnpm run dev --host 0.0.0.0` — Vite with
  HMR on `VITE_PORT`. Chokidar polling (inotify is unreliable across the
  bind mount).
- **prod** (`prod.yml` + `prod.Dockerfile`): two-stage — pnpm
  `--frozen-lockfile` install + `vite build`, then nginx serving only static
  files. At startup it copies `manifest.json` into the shared
  `vite-manifest` volume, where the prod php image's
  `public/build/manifest.json` symlink picks it up. That's how php knows the
  hashed filenames without sharing a filesystem with the assets container.

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

### queue

Prod-only (dev uses `QUEUE_CONNECTION=deferred`, so no workers needed).
Builds the `queue` stage; supervisord runs 3 workers
(`--tries=3 --max-time=3600`), config in `supervisord.conf`.

### mailhog

SMTP sink + web UI, present in dev/staging/prod-preview so no environment
can ever send real mail.

### e2e

The E2E runner: `mcr.microsoft.com/playwright:v<X>-noble` (node + browsers +
system deps) with PHP 8.4 (ondrej PPA), composer, pnpm, GLPK (real lottery
solver), and ffmpeg added on top. Everything the browser journeys need lives
in this one container because the **official** pest-plugin-browser assumes a
single host: it serves the Laravel app **in-process** (an Amp socket server
inside the pest process — which is why `actingAs()`, event listeners, and
Eloquent all work mid-journey) and spawns `node_modules/.bin/playwright
run-server` on localhost. The container idles; the `mtav e2e` flow execs
build/test steps into it. See `tests/Browser/README.md` for the suite.

## Compositions

### dev (`mtav-dev`)

Includes php, nginx, mysql, assets, mailhog; wires reverb (`depends_on`
mysql). Ports: app **8000**, Vite **5173**, mysql **3307** (localhost only),
mailhog **1025/8025**, reverb **8080**.

### testing (`mtav-testing`)

Includes php and nginx; wires reverb, tmpfs-mysql, and assets with
`command: tail -f /dev/null` — in testing the assets container is a **node
toolbox**, not a dev server (Vitest is headless), and an idle command means
exec'd `pnpm install/test` never race a boot-time install. Ports: app
**8001**, mysql **3308**, reverb **8082**, Vite unpublished. Sets
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
down. Four journey tests cover the app's main use cases end to end
(`tests/Browser/README.md`); full run ≈ 2 min.

### staging (`mtav-staging`)

Includes php, nginx, mysql, mailhog; wires reverb; declares assets behind
`profiles: [tools]` so it **never starts with `up`** — `mtav staging` runs
it once via `compose run --rm assets 'pnpm install && pnpm run build'`.
Deliberately uses **dev's ports** (staging is "dev, but frozen for a look"
— you stop dev, you spin staging). Sets `VITE_FORCE_BUILD=true`.

### prod (`mtav-prod`)

Everything via `extends` (no `include`) because every service needs
composition-level wiring: `depends_on` chains and the shared named volumes
(`mysql-data`, `app-storage`, `vite-manifest`) declared once at the bottom.

Boot order enforced by compose: mysql healthy → `migrations` (one-shot,
`php artisan migrate --force`, `restart: "no"`) completes successfully →
php + queue start; nginx waits for php + assets. On every re-`up`, compose
re-runs the exited migrations container (idempotent) and leaves running
services untouched unless their image or config changed — which is exactly
how `mtav prod build && mtav prod` rolls a new version: only containers
whose images changed get recreated.

Ports (9xxx block so a preview coexists with dev/testing): app **9000**,
mailhog **9025/9825**, reverb **9080**, mysql unpublished. On a real server,
override in `docker/prod/.env.local` and point the host nginx (SSL
termination) at `NGINX_PORT`.

## Vite: HMR vs built assets

laravel-vite-plugin writes `public/hot` (containing the dev-server URL)
while `vite dev` runs and removes it on clean shutdown; Laravel's `@vite`
serves HMR URLs iff that file exists, else it reads
`public/build/manifest.json`. Because dev/testing/staging share one bind
mount, that single file used to flip environments unpredictably (stale hot
after a crash → staging serves dead HMR URLs; a test run deleting hot →
dev silently degrades). The rules are now deterministic:

- **dev**: always HMR. Vite rewrites `public/hot` on every container start,
  and nothing else is allowed to delete it. A leftover `public/build/` is
  irrelevant (hot wins).
- **testing / staging / prod**: always built. Their php containers set
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
- **`staging`** — a *re-spin*, not a daemon: every invocation refreezes the
  snapshot (rebuild assets from current code, composer install, migrate —
  seed only when the volume is fresh — storage:link, config/route/view
  cache). Re-running it is the way to update the snapshot. Prints the
  return-to-dev cleanup reminder. Caveat: PHP code is still bind-mounted, so
  edits after spinning do apply on the next request — only assets and caches
  are frozen. If you need a fully frozen artifact, that's what prod is.
- **`prod [up]`** — frozen semantics: no `--build`, compose only builds if an
  image doesn't exist at all. `prod build` is the explicit, deliberate bake.
  `prod down` keeps volumes (data survives re-releases).
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

## History & context

Replaced the previous `.docker/` setup (removed 2026-07-12): one monolithic
compose file juggling dev/testing via profiles, ~20 wrapper scripts
exporting env vars, a parallel `build/` tree pushing versioned images to
ghcr.io, and an `mtav-deploy` submodule for server-side pulls. All of that
collapsed into the layout above; the registry/versioning machinery was
dropped deliberately — there is no production deployment yet, and when one
happens the simplest path is clone + `mtav prod build && mtav prod` behind
the host nginx (a registry can be reintroduced then if ever needed).

The Browser suite was rewritten from scratch alongside this infra (2026-07):
the old 205 page-by-page tests and the forked pest-plugin-browser (which
existed only to reach a Playwright server in another container) were replaced
by four journey tests on the official plugin in the all-in-one `mtav-e2e`
composition. `mtav e2e` is the pre-deployment safety net (~2 min); the CI
step remains opt-in (commented in `.github/workflows/tests.yml`) since the
suite is meant to be run deliberately before deploying, not on every push.
