# Delivery

How MTAV goes from a commit to a running site: what runs on every push, how a release
is built, and how it reaches staging and production.

## The pipeline

`.github/workflows/pipeline.yml`, on GitHub Actions:

| Step | When | What |
|---|---|---|
| Style, lint and types | every push | Pint, ESLint, Prettier, TypeScript |
| Tests | every push | Pest (business rules, lottery) and Vitest, in `docker/testing` |
| Browser suite | every push | every page, form and journey in Chromium, in `docker/e2e` |
| Release image | every push, after the three above | built every time; published on `main` (or by hand) |
| Deploy to staging | `main` | the published image, to `stg.mtav.uy` |

Pushes to this repository run on push; pull requests from forks run on the pull request.
A newer push to a branch cancels its run in progress; on `main` every commit runs to the
end.

Production has its own workflow, `.github/workflows/production.yml`, started by hand
("Run workflow"): it deploys the release staging runs, or one named, once the
`production` environment's reviewer approves.

## The release image

`deploy/Dockerfile` builds one image with the whole app: the frontend built with Vite,
the PHP code with its production dependencies, on FrankenPHP (Caddy and PHP in one
process). The same image runs every role, chosen by its command, and every environment:
nothing about an environment is built in. Settings come from the environment at run
time, including where browsers reach the websocket server (the `realtime` shared prop).

It is published to `ghcr.io/devvir/mtav`, tagged by commit (`sha-<commit>`); `main` also
tags the latest as `main`. The package is public, like the code: hosts pull it without
credentials. A release is an image tag: deploying means running an
environment on it.

On start, every role builds Laravel's caches from its environment
(`deploy/entrypoint.sh`), then runs its command.

## An environment

`deploy/compose.yml` is one environment on a host, the compose project
`mtav-<environment>`:

| Service | Runs |
|---|---|
| `app` | the web server: pages, assets, uploads |
| `reverb` | the websocket server (live updates) |
| `worker` | the queue (the lottery, invitation emails) |
| `db` | MariaDB |

Uploaded files and the database live in volumes that releases never replace. Settings
and secrets live in the environment's `.env` on the host (template: `deploy/env.example`),
never in the repository.

In front of every environment, the host runs one proxy (`deploy/edge`): Caddy, which
takes ports 80 and 443, gets and renews the TLS certificates, and hands each domain to
its environment over the `mtav-edge` network (the websocket server under `/app`, the web
server for the rest). A new environment is a new block in `deploy/edge/Caddyfile`.

## Deploying

The pipeline reaches the host by SSH with a key of its own, which can run one thing: the
gate (`deploy/host/gate`, installed on the host as the key's forced command). The gate
accepts `deploy <environment> <image>`, with the commit's deploy bundle (`deploy/`) on
stdin, and `release <environment>`, which says what an environment runs. It refuses
anything else, any image that is not MTAV's, and any environment not set up on the host.

The deploy itself (`deploy/host/deploy`, from the bundle, so it changes with the code):

1. starts the proxy, or reloads its configuration;
2. pulls the image and makes sure the database is up;
3. dumps the database (`<environment>/backups/`, the last 20 kept);
4. migrates, with the new image;
5. starts every role on the new image and waits for them to report healthy;
6. if they do not, starts the previous image again and fails.

Migrations run forward only. When a release has migrated and then failed, the previous
image runs on the migrated database; if that does not work, restoring the dump taken in
step 3 is a person's decision:

```sh
cd ~/delivery/staging
gunzip -c backups/<dump>.sql.gz | docker compose -p mtav-staging exec -T db \
  sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"'
```

Only one deploy per environment runs at a time (the gate holds a lock, and the
workflows queue deploys per environment).

## Setting up a host

Once per host, as the user that runs the environments (with Docker, no sudo needed):

1. **The delivery home**: `mkdir -p ~/delivery/bin` and copy `deploy/host/gate` to
   `~/delivery/bin/gate`.
2. **The pipeline's key**: generate a key pair for the pipeline, and add its public half
   to `~/.ssh/authorized_keys`, bound to the gate:

   ```
   command="~/delivery/bin/gate",restrict ssh-ed25519 AAAA… mtav-pipeline
   ```

3. **Each environment**: `mkdir ~/delivery/<environment>` and write its `.env` from
   `deploy/env.example` (a new `APP_KEY`, database password and Reverb keys each).
4. **GitHub**: in the repository's environments (`staging`, `production`), the secrets
   `DEPLOY_SSH_KEY` (the private half), `DEPLOY_KNOWN_HOSTS` (`ssh-keyscan <host>`) and
   `DEPLOY_TARGET` (`user@host`).
5. **DNS**: each domain in `deploy/edge/Caddyfile` points at the host; ports 80 and 443
   are free for the proxy.

The first deploy creates the proxy, the volumes and the database; the database starts
empty (the demo data's seeder needs development packages the image leaves out).

## Who decides what runs

- **Staging** deploys every green `main`, once the `staging` environment allows it. While
  the environment has a required reviewer, each deploy waits for that approval.
- **Production** deploys only by hand, and only once its reviewer approves.
