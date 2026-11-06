# Delivery

How MTAV goes from a commit to a running site: what runs on every push, how a release
is built, and how it reaches each environment.

## The pipeline

On GitHub Actions:

| Workflow | When | What |
|---|---|---|
| `pipeline.yml` | every push | style, lint, types and static analysis; tests (Pest, Vitest); the browser suite; the release image |
| `solver.yml` | every push that touches the lottery's solvers; by hand | the Stress suite: GLPK on hard instances. It reports on the commit and holds no release back |
| | `staging`, once all of that passes | the image published, and staging deployed |
| | `main`, once all of that passes | the image published (and tagged `main`) |
| `dev.yml` | every push to `dev`; by hand, from any branch | the branch's image (built if not yet published), deployed to dev, from fresh demo data |
| `production.yml` | by hand, from `main` | the release staging runs (or a commit on `main`), deployed to production or to sorteo (the Open Lottery variant) |
| `rollback.yml` | by hand | an environment back on the release before |
| `fresh.yml` | by hand | dev or staging started over with the demo data, on the release it runs |
| `watch.yml` | every morning | every site answers, and every certificate has three weeks left |

Pushes to this repository run on push; pull requests from forks run on the pull request.
A newer push to a branch cancels its run in progress; on `staging` and `main` every commit
runs to the end. Deploys to one environment queue, never overlap.

A failed `watch` run is GitHub's mail to the repository's owner. Certificates renew by
themselves; the watch says when that did not happen.

## The release image

`deploy/Dockerfile` builds one image with the whole app: the frontend built with Vite,
the PHP code with its production dependencies, on FrankenPHP (Caddy and PHP in one
process), unprivileged, listening on 8080. The same image runs every role (web server,
websocket server, queue worker), chosen by its command, and every environment: nothing
about an environment is built in. It also carries its own deploy steps (`deploy/`).

What it is built with cannot change under it: base images by digest, the pnpm download by
checksum, third-party actions by commit. Dependabot proposes newer ones each month, to `dev`
(`.github/dependabot.yml`).

`release-image.yml` builds it and publishes it to `ghcr.io/devvir/mtav`, tagged by commit
(`sha-<commit>`); `main` also tags the latest as `main`. The package is public, like the
code: hosts pull it without credentials. Nothing is ever built on a host.

On start, every role builds Laravel's caches from its environment
(`deploy/entrypoint.sh`), then runs its command.

Every image, here and in development and tests, names an exact version (and CI an exact
runner), so what staging tried is what production runs; versions change only by commit.

## An environment

An environment is two compose files over one directory of settings:

- `deploy/compose.yml`, the compose project `mtav-<environment>`: what stays while
  releases come and go. The database, and the **switch** (nginx, unprivileged): the
  environment's front door, on two ports of the host's loopback (`MTAV_WEB_PORT` for
  pages, `MTAV_WS_PORT` for the websocket server), handing both to the live slot.
- `deploy/slot.yml`, the projects `mtav-<environment>-blue` and `-green`: a release, in
  one of two **slots** (`app`, `reverb`, and a worker per queue: `worker` for the main one,
  with lottery runs and mail; `worker-realtime` for live updates and devices; `worker-assistant`
  for the Assistant's answers). Both slots share the database and the
  uploaded files (the volume `mtav-<environment>_files`).

Settings and secrets live in the environment's `.env` on the host (template:
`deploy/env.example`), never in the repository. Each environment's ports are fixed, so
whatever is in front of it (a proxy terminating TLS, a load balancer) points at them
once.

## Deploying

A deploy is blue/green: the release starts in the slot that is not live, and only once it
is healthy does the switch hand it the traffic, so visitors never see the site down.

1. the image is pulled; the database is up;
2. the database is dumped (`backups/`, for root alone, the last 20 kept);
3. migrations run, while the live release keeps serving (so a release's migrations must
   leave the previous release working: add first, remove in a later release);
4. the release starts in the other slot, and must report healthy within three minutes;
5. the switch goes over to it, and must answer through it;
6. the slot before is taken down: nothing of an old release stays running, and going back
   starts it again from its image (about a minute); a worker with a job in hand finishes it
   first (up to 11 minutes), and the deploy waits for it.

If step 4 or 5 fails, the new slot is removed and the live release never stopped. With
`fresh` (only where the environment's `.env` says `MTAV_FRESH_ALLOWED=true`, and never on
production, whatever its `.env` says: the gate and the deploy refuse it, and the app refuses
its own wiping commands there), step 3
starts the database and the uploaded files over, with the demo data instead, and the
dump of step 2 is the only copy of what was there. Every deploy to dev is fresh; staging
is fresh only when `fresh.yml` asks: it deploys again the release staging runs, fresh.

`rollback` puts the release before back on the switch (its slot starts again if it
had stopped). Migrations run forward only: the release before runs on the newer
database. If that does not work, restoring a dump from step 2 is a person's decision:

```sh
cd /mtav/<environment>
sudo gunzip -c backups/<dump>.sql.gz | docker compose -p mtav-<environment> exec -T db \
  sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'
```

## Reaching a server

MTAV runs on AWS (`infra/aws`, OpenTofu: the servers, the mtav.uy zone, backups and the
deploy identities, each file saying what it makes). The only step that knows where
environments live is `deploy/ci/deploy`: it finds the server running the environment and
hands it a request through AWS Systems Manager. Each GitHub environment has an AWS identity
of its own (`mtav-deploy-<environment>`), which can run one thing on one server, the
**gate** (`deploy/host/gate`, installed as `/usr/local/sbin/mtav-gate`), for its own
environment only, and ask any server what it runs. The gate accepts:

| Request | Does |
|---|---|
| `deploy <environment> <commit> [fresh]` | that commit's release goes live |
| `rollback <environment>` | the release before goes live again |
| `status` | what each environment runs (live and previous: slot and image) |
| `backup <environment> database\|files` | a gzipped copy, streamed back ([data.md](data.md)) |

It refuses anything else, any environment not set up on the server, and any image not built
from this repository. Backups are the server's own (nightly, to S3); the deploy identities
never take them. The deploy's steps come from the release's own image
(`deploy/host/deploy`), never from the caller, so an identity can only run MTAV's releases.

Servers are reached only through Systems Manager: no SSH, anywhere.

## Setting up a server

1. **The server**: `infra/aws/main` makes it (`tofu apply`), with its data disk, its
   address and its settings; its first boot runs `server/setup.sh`, which installs Docker,
   nginx in front (HTTPS with its own Let's Encrypt certificate, TLS 1.2 and 1.3, no
   version shown, uploads up to 105 MB), the gate and nightly backups. Every step can run
   again. Which environments a server runs, and the names each answers to, are in
   `main.tf`; `mtav-reconfigure` on the server takes a change up. CloudFront can stand in
   front of the servers (`cloudfront` in `main.tf`); without it, each name leads straight
   to its server.
2. **Each environment**: `/mtav/<environment>/.env` on its server (written there through
   Systems Manager), from `deploy/env.example`: its ports (as in `main.tf`), a new
   `APP_KEY`, database password and Reverb keys. An environment
   on demo data (dev, and staging) needs `MTAV_FRESH_ALLOWED=true` and `DEMO_PASSWORD`,
   the demo accounts' password (`superadmin@example.com`, `admin@example.com`,
   `member@example.com`, …). An environment without demo data needs its owner, its first
   superadmin: `APP_OWNER`, as `'name:email:password hash'` (single quotes: a hash holds `$`; the
   password itself is never written down), taken while it has
   no account at all; until
   then the site shows only that it is under construction. Where demo data is seeded,
   its first account is the owner. Signing in with
   Google needs `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`, from a Google Cloud OAuth
   client (Web application) whose authorised redirect URIs include
   `<APP_URL>/auth/google/callback` (one client serves every environment, each with its
   own URI); without them the option is not offered. Notifications on devices need
   `VAPID_PUBLIC_KEY` and `VAPID_PRIVATE_KEY`, a pair of its own per environment
   (`php artisan push:keys`). `CONTACT_EMAIL` is whom the privacy page tells people to
   write to (else the owner). The
   console's Deployments page needs `DEPLOYERS` (the superadmins who may deploy, by
   email) and `GITHUB_DEPLOY_TOKEN` (a fine-grained token for this repository only:
   Actions read and write; Deployments, Contents and Metadata read); without either,
   the page is hidden.
3. **The proxy in front** (written by the server's `mtav-front`): each name to its
   environment's ports, the websocket path (`/app`) to `MTAV_WS_PORT`, the rest to
   `MTAV_WEB_PORT`, with `X-Forwarded-For` and `X-Forwarded-Proto` set. The app ignores
   forwarded hosts and ports, and takes any host but `APP_URL`'s (with or without www) for
   `APP_URL`'s, so a wrong header can never send a link anywhere else.
4. **GitHub**: `infra/aws/deploy-keys.sh` gives each environment of a running server its
   identity's key, as that GitHub environment's secrets (`AWS_ACCESS_KEY_ID`,
   `AWS_SECRET_ACCESS_KEY`), and rotates them when run again. The environments keep their
   branches: staging only from `staging`, production only from `main`, dev from any (by
   hand). A workflow of any other branch never sees a key that could reach them.

The first deploy creates the switch, the volumes and the database.

## Who decides what runs

Each environment follows a branch of its own: deploying is pushing.

- **Dev** deploys every push to `dev`, by itself, untested, from fresh demo data (dev is
  where things are tried); by hand, any other branch.
- **Staging** deploys every green push to `staging`, by itself, keeping its data; it
  starts over with the demo data only by hand.
- **Production** deploys only by hand, from `main`: by default the release staging runs.

Commits move up by fast-forward only: `staging` to `main` always (so production runs the
commit, and the image, staging ran), `dev` to `staging` as fits. A push to `main` of a
commit `staging` never had fails the pipeline, and production refuses it.

The platform console's Deployments page starts these same workflows through GitHub's API
(dev from a branch, production from `main`, a rollback of any environment, dev or
staging started over) and shows
what each environment runs: GitHub's deployment records, replayed in order (a rollback
swaps the live release with the one before, starting over keeps it), against the latest
`main`. A record is of the commit its run was on; production's runs are on `main`, so
they name the release they deployed in the record's address, after a `#`.
