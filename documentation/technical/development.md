# Development

Everything runs in Docker, driven by one script at the root, `./mtav`. Nothing but
Docker (with Compose) and Bash is needed on the machine.

## First run

```sh
git clone https://github.com/devvir/mtav && cd mtav
./mtav up
```

On a fresh clone, `up` writes `.env` from `.env.template` (with its own `APP_KEY`),
builds the images, installs the PHP and JavaScript dependencies, migrates and seeds the
demo data. Then:

| What | Where |
|---|---|
| The app | http://localhost:8000 (demo accounts: see `database/seeders`, password `password`) |
| Mail it sends | http://localhost:8025 (Mailpit) |
| Vite (hot reload) | port 5173 |
| MySQL | port 3307 |
| Reverb (live updates) | port 8080 |

Ports come from `docker/dev/.env`; personal overrides go in `docker/dev/.env.local`
(not versioned). `./mtav up` never wipes data: it migrates. `./mtav fresh` is the one
command that starts the database over.

## Three environments, side by side

Each is its own Compose project, so tests run while dev keeps running:

| Environment | Project | For | Assets |
|---|---|---|---|
| `dev` | `mtav-dev` | working on the app | Vite's hot reload, always |
| `testing` | `mtav-testing` | Pest and Vitest | built |
| `e2e` | `mtav-e2e` | the browser suite (Pest + Playwright) | built |

Compositions live in `docker/<environment>/compose.yml`, built from the services in
`docker/services/` (php, nginx, mysql, assets, reverb, mailpit, e2e). Settings layer:
`.env`, then `docker/<environment>/.env`, then `docker/<environment>/.env.local`.

Testing and e2e set `VITE_FORCE_BUILD=true`, so they serve the built assets even while dev
writes `public/hot` (never delete it while dev runs: dev would fall back to stale built
assets). The e2e run builds the assets first and removes `public/build` when it ends.

## Commands

| Command | Does |
|---|---|
| `./mtav up` / `stop` / `down` / `restart` | start, stop, remove, cycle the dev environment |
| `./mtav status` / `logs [service]` | what runs, and where; follow logs |
| `./mtav fresh` | everything rebuilt, database started over with demo data |
| `./mtav update` / `rebuild [service]` | dependencies and images brought up to date |
| `./mtav artisan …` / `composer …` / `pnpm …` / `tinker` / `shell` | inside the dev containers |
| `./mtav pest [args]` | PHP tests (starts and stops `testing`) |
| `./mtav vitest [args]` | frontend unit tests |
| `./mtav e2e [args]` | the browser suite (starts and stops `e2e`) |
| `./mtav test` | all three |
| `./mtav precommit` | Pest and Vitest without the slow group |
| `./mtav release` | the release image, built as the pipeline builds it |
| `./mtav compose <env> …` | raw Docker Compose against an environment |

Never run two invocations of the same kind of tests at once: Pest and Vitest share
`mtav-testing`, the browser suite `mtav-e2e`. Tests refuse to run anywhere but
`APP_ENV=testing`, so they can never touch the dev database.

## Things to know

- **pnpm is strict**: a package used directly must be in `package.json`. Packages allowed
  to run install scripts are listed in `pnpm-workspace.yaml` (`allowBuilds`).
- **Playwright's version is pinned twice**: the e2e image
  (`docker/services/e2e/Dockerfile`) and the `playwright` dev dependency must match.
- **Every image names an exact version**, here, in CI and in production, so versions
  change only by commit.
- A stale `bootstrap/cache/config.php` makes everything read old settings:
  `./mtav artisan optimize:clear`.
- Optional features stay off until configured in `.env`: Google sign-in
  (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`), notifications on devices
  (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, from `./mtav artisan push:keys`).

## Code style

Pint (PHP), ESLint and Prettier (TypeScript, CSS) and `tsc` run in the pipeline and in
the pre-commit hook. Comments say what something is for, in plain words; names say what
things are. Every fix comes with a test that would have caught it.
