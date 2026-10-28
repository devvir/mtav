# Delivery

How MTAV goes from a commit to a running site: what runs on every push, how a release
is built, and how it reaches each environment.

## The pipeline

On GitHub Actions:

| Workflow | When | What |
|---|---|---|
| `pipeline.yml` | every push | style, lint and types; tests (Pest, Vitest); the browser suite; the release image |
| | `main`, once all of that passes | the image published, and staging deployed |
| `dev.yml` | by hand, from any branch | the branch's image (built if not yet published), deployed to dev; optionally from fresh demo data |
| `production.yml` | by hand, from `main` | the release staging runs (or a commit on `main`), deployed to production |
| `rollback.yml` | by hand | an environment back on the release before |
| `watch.yml` | every morning | every site answers, and every certificate has three weeks left |

Pushes to this repository run on push; pull requests from forks run on the pull request.
A newer push to a branch cancels its run in progress; on `main` every commit runs to the
end. Deploys to one environment queue, never overlap.

A failed `watch` run is GitHub's mail to the repository's owner. Certificates renew by
themselves; the watch says when that did not happen.

## The release image

`deploy/Dockerfile` builds one image with the whole app: the frontend built with Vite,
the PHP code with its production dependencies, on FrankenPHP (Caddy and PHP in one
process), unprivileged, listening on 8080. The same image runs every role (web server,
websocket server, queue worker), chosen by its command, and every environment: nothing
about an environment is built in. It also carries its own deploy steps (`deploy/`).

`release-image.yml` builds it and publishes it to `ghcr.io/devvir/mtav`, tagged by commit
(`sha-<commit>`); `main` also tags the latest as `main`. The package is public, like the
code: hosts pull it without credentials. Nothing is ever built on a host.

On start, every role builds Laravel's caches from its environment
(`deploy/entrypoint.sh`), then runs its command.

## An environment

An environment is two compose files over one directory of settings:

- `deploy/compose.yml`, the compose project `mtav-<environment>`: what stays while
  releases come and go. The database, and the **switch** (nginx, unprivileged): the
  environment's front door, on two ports of the host's loopback (`MTAV_WEB_PORT` for
  pages, `MTAV_WS_PORT` for the websocket server), handing both to the live slot.
- `deploy/slot.yml`, the projects `mtav-<environment>-blue` and `-green`: a release, in
  one of two **slots** (`app`, `reverb`, `worker`). Both slots share the database and the
  uploaded files (the volume `mtav-<environment>_files`).

Settings and secrets live in the environment's `.env` on the host (template:
`deploy/env.example`), never in the repository. Each environment's ports are fixed, so
whatever is in front of it (a proxy terminating TLS, a load balancer) points at them
once.

## Deploying

A deploy is blue/green: the release starts in the slot that is not live, and only once it
is healthy does the switch hand it the traffic, so visitors never see the site down.

1. the image is pulled; the database is up;
2. the database is dumped (`backups/`, the last 20 kept);
3. migrations run, while the live release keeps serving (so a release's migrations must
   leave the previous release working: add first, remove in a later release);
4. the release starts in the other slot, and must report healthy within three minutes;
5. the switch goes over to it, and must answer through it;
6. the slot before stays up without its worker, so going back is instant.

If step 4 or 5 fails, the new slot is removed and the live release never stopped. With
`fresh` (only where the environment's `.env` says `MTAV_FRESH_ALLOWED=true`), step 3
starts the database over, with the demo data instead.

`rollback` puts the release before back on the switch (its slot starts again if it
had stopped). Migrations run forward only: the release before runs on the newer
database. If that does not work, restoring a dump from step 2 is a person's decision:

```sh
cd /mtav/<environment>
gunzip -c backups/<dump>.sql.gz | docker compose -p mtav-<environment> exec -T db \
  sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'
```

## Reaching a host

The only step that knows where environments live is `deploy/ci/deploy`: it hands a
request to the host by SSH. On a single Linux host, the pipeline's key belongs to a user
of its own (`mtav-deploy`: no password, no Docker) and can run one thing, the **gate**
(`deploy/host/gate`, installed as `/usr/local/sbin/mtav-gate`, through sudo). The gate
accepts:

| Request | Does |
|---|---|
| `deploy <environment> <commit> [fresh]` | that commit's release goes live |
| `rollback <environment>` | the release before goes live again |
| `status` | what each environment runs (live and previous: slot and image) |

It refuses anything else, any environment not set up on the host, and any image not built
from this repository. The deploy's steps come from the release's own image
(`deploy/host/deploy`), never from the caller, so the key can only run MTAV's releases.

Moving to a cloud provider replaces this step (and the host's proxy) with the provider's
own: the image, the roles and the slots stay as they are.

## Setting up a host

Once per host (Ubuntu, with Docker, and nginx in front terminating TLS):

1. **The host**: copy `deploy/host/setup`, `deploy/host/gate` and the pipeline's public
   key to it, then `sudo bash setup <key>.pub`. It installs security updates (and
   unattended ones from then on), stops services nothing uses, limits nginx to TLS 1.2
   and 1.3 (no version shown, uploads up to 105 MB), makes Docker publish on the
   loopback by default, renews the certificates and reloads nginx after every renewal,
   and creates `mtav-deploy` with the gate. Every step can run again.
2. **Each environment**: `/mtav/<environment>/.env`, written from `deploy/env.example`
   (its ports, a new `APP_KEY`, database password and Reverb keys).
3. **The proxy in front**: each domain to its environment's ports, the websocket path
   (`/app`) to `MTAV_WS_PORT`, the rest to `MTAV_WEB_PORT`.
4. **GitHub**: the repository's secrets `DEPLOY_SSH_KEY` (the private half),
   `DEPLOY_KNOWN_HOSTS` (`ssh-keyscan <host>`) and `DEPLOY_TARGET` (`mtav-deploy@<host>`);
   the environments `staging`, `dev` and `production`, production limited to `main`.

The first deploy creates the switch, the volumes and the database.

## Who decides what runs

- **Staging** deploys every green `main`, by itself.
- **Dev** deploys whatever branch someone picks, by hand.
- **Production** deploys only by hand, from `main`: by default the release staging runs.

The deployment tools in the platform console (planned) will start these same workflows.
