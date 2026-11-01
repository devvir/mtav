# Platform console, operations, data layer & CI/deploy
_Review of main · scope: app/Http/Controllers/Platform, app/Services/{ErrorLog,ActivityLog,ArchiveService}, app/Services/Delivery, app/Services/Queue, app/Console, database/migrations, seeders (production safety), config, docker/, deploy/, dev/, .github/workflows, build manifests_

## Summary
The console's controllers are small and mostly sound. `/admin` sits behind auth, verified and `EnsureSuperadmin`. Deploying needs a second gate on top of that: the `deploy` ability, which checks a `DEPLOYERS` email allow-list and needs a configured GitHub token (`AppServiceProvider.php:63-65`). All four dispatch endpoints allow-list their inputs. Production refuses wiping commands (`DB::prohibitDestructiveCommands`). The host gate is written with care: it validates arguments with regexes, scopes each key to one environment, refuses `fresh` on production twice, and keeps backups on their own key.

The biggest risk is how the delivery chain trusts code. The gate runs the deploy script and the compose files found in the image of whatever commit it is given, as root. Dev can be deployed from any branch. All environments share one host. Together, these let anyone who can push a branch get root on production. Image tags are mutable and any branch can publish to the registry, so even an approved production deploy can be handed a poisoned image.

Other important issues:
- **Data:** a RESTRICT foreign key next to the project cascade makes "delete for good" fail for any project that has units.
- **Password reset:** trusted `X-Forwarded-Host` probably allows poisoning of password-reset links.
- **Deployments page:** it misreports what production runs after a deploy of a named commit, and after `fresh`.
- **Operations:** the error monitor merges every vendor-thrown error into one entry, and nothing ever prunes the logs or the cache.

## Findings

| # | Severity | Category | Title | Location |
|---|---|---|---|---|
| 05-01 | critical | security | Any branch's code runs as root on the shared host: the dev deploy key reaches production | `deploy/host/gate:96-101` |
| 05-02 | high | security | Mutable image tags and registry write from every branch: production can be handed a poisoned release | `.github/workflows/release-image.yml:26-28,60-68` |
| 05-03 | high | correctness | Deleting a project for good fails once it has units (RESTRICT foreign key beside the cascade) | `database/migrations/2026_10_27_000001_create_projects_tables.php:39,50` |
| 05-04 | high | security | Trusted `X-Forwarded-Host` allows host-header poisoning of password-reset links | `bootstrap/app.php:26-37` |
| 05-05 | medium | correctness | Deployments page reports the wrong release after a deploy of a named commit, a `fresh`, or an aged-out rollback | `app/Services/Delivery/GitHub.php:112-150` |
| 05-06 | medium | security | A superadmin can demote the owner (and any superadmin), then block or delete them | `app/Http/Controllers/Platform/UserController.php:68-73,97-108` |
| 05-07 | medium | correctness | Error monitor merges all vendor-thrown errors into one entry and stops alerting | `app/Services/ErrorLog.php:28-37` |
| 05-08 | medium | security | CI and image builds depend on mutable tags: actions, base images and the pnpm tarball are not pinned or verified | `.github/workflows/release-image.yml:38-60`, `deploy/Dockerfile:17` |
| 05-09 | medium | performance | Nothing prunes the activity log, error log, database cache or failed jobs; the activity page counts the whole table | `routes/console.php:1-8`, `deploy/slot.yml:26-61` |
| 05-10 | low | performance | Deployments page makes up to ~41 sequential GitHub calls; GitHub outages surface as 500s | `app/Services/Delivery/GitHub.php:33-49,115-116` |
| 05-11 | low | security | Error log stores reset tokens and personal data from URLs and SQL messages, shows them to all superadmins and emails them | `app/Services/ErrorLog.php:34,43,46` |
| 05-12 | low | security | Pre-deploy database dumps are written world-readable on the host | `deploy/host/deploy:94-101` |
| 05-13 | low | correctness | A deploy can kill a running lottery: the worker's grace period is shorter than the lottery job's timeout | `deploy/slot.yml:54-58` |
| 05-14 | low | correctness | Array query parameters crash the console's list pages with a TypeError | `app/Http/Controllers/Platform/ActivityController.php:28` |
| 05-15 | low | accessibility | Errors page: 30 identical "Solved" buttons delete entries with no accessible context | `resources/js/pages/Platform/Errors.tsx:54-63` |
| 05-16 | low | security | Production host IP and a Docker-capable SSH login are committed in the repository | `dev/warden/sources.mjs:8` |

### 05-01. Any branch's code runs as root on the shared host: the dev deploy key reaches production
- **Severity / category:** critical / security
- **Location:** `deploy/host/gate:96-101`, `deploy/host/gate:56-63`, `deploy/host/deploy:88,108-118`, `deploy/host/setup:121,131`, `.github/workflows/dev.yml:8-11,29-45`, `deploy/Dockerfile:71-73`, `documentation/technical/delivery.md:96-100,152-156`
- **Problem:** The gate runs as root through sudo (`setup:131`). For `deploy dev <commit>` it pulls `ghcr.io/devvir/mtav:sha-<commit>` and checks only the `org.opencontainers.image.source` label, which any build can set. It then copies `/app/deploy` out of that image and runs `bash $bundle/host/deploy` as root (`gate:100-101`). That script in turn runs the image's own `compose.yml`/`slot.yml` with root's Docker (`deploy:88`). By design, `dev.yml` can be dispatched on any branch, and the `dev` GitHub environment, which holds the dev `DEPLOY_SSH_KEY`, is open to every branch ("dev from any", `delivery.md:155`). The per-key restriction (`command="sudo -n mtav-gate dev"`) only limits which arguments the gate accepts. It does not limit what the script or compose files inside the image do. All environments live on one host under `/mtav/*` (`delivery.md:97`: "On a single Linux host"; `DEPLOY_TARGET` is one repository-wide secret). The documented guarantee ("A workflow of any other branch never sees a key that could reach them", `delivery.md:156`) therefore does not hold.
- **Failure scenario:** Someone with push access to any branch, or a compromised collaborator account, pushes branch `x`. In it, `deploy/host/deploy` starts with `cat /mtav/production/.env; docker exec mtav-production-db-1 mariadb-dump …`, or `slot.yml` bind-mounts `/` into a container. They run `dev.yml` on `x`, or a deployer clicks "Deploy dev from x" in the console. The code runs as root on the production host and exposes production's `APP_KEY`, the DB password and all personal data, or replaces production's containers. This bypasses branch protection on `main`/`staging` and the production environment's approval entirely.
- **Recommended fix:** Never execute deploy logic or compose files taken from an image that untrusted refs can produce. Keep the deploy steps and compose files root-owned on the host, installed with the gate from a reviewed commit, and pass the image only as data. Isolate environments that trust different branches: put dev on a separate host or VM from production. Docker access is root-equivalent, so per-user separation on one Docker daemon is not enough. Verify production images by digest or signature (see 05-02), not by label.
- **Verification note:** Read the gate, the deploy script, the setup sudoers/authorized_keys lines, `dev.yml` and `delivery.md`; nothing in the chain constrains the bundle's contents.

### 05-02. Mutable image tags and registry write from every branch: production can be handed a poisoned release
- **Severity / category:** high / security
- **Location:** `.github/workflows/release-image.yml:26-28,60-68`, `.github/workflows/pipeline.yml:112-119`, `.github/workflows/dev.yml:17-23`, `.github/workflows/production.yml:38-48`, `deploy/host/gate:96-99`
- **Problem:** Production deploys by tag. The gate pulls `sha-<commit>`, and `production.yml` only checks that the commit is on `main` and `staging`. GHCR tags are mutable. Every push to any branch runs workflows from that branch's own files, and the `image` job already requests `packages: write`. A branch can therefore edit `release-image.yml`, or add any workflow, to push an image under any tag, including the tag of the commit staging runs. The `buildcache` "only main writes it" rule (`release-image.yml:72-76`) has the same weakness, because a branch can edit the condition.
- **Failure scenario:** A branch workflow pushes a backdoored image tagged `sha-<commit staging currently runs>`. The next approved production deploy defaults to "the release staging runs" and pulls that tag. Every check passes: the commit is on main and staging, and the label matches. Production then ships the attacker's image.
- **Recommended fix:** Grant `packages: write` only in jobs gated to `refs/heads/main`/`staging`. Enforce that with a protected environment holding a registry credential, because job-level `permissions:` is controlled by the branch. Feature branches should build without pushing, or push to a separate package. Record the digest when staging deploys and deploy production as `image@sha256:…`. Alternatively, sign images in main/staging workflows (cosign or attestations) and verify the signature in the gate.
- **Verification note:** Confirmed `push:` on `'**'` with a `packages: write` job, a tag-only pull in the gate, and no digest anywhere in the production path.

### 05-03. Deleting a project for good fails once it has units (RESTRICT foreign key beside the cascade)
- **Severity / category:** high / correctness
- **Location:** `database/migrations/2026_10_27_000001_create_projects_tables.php:28,39,50`, `app/Services/ProjectService.php:130-137`, `app/Http/Controllers/Platform/ProjectController.php:79-87`
- **Problem:** `purge()` relies on `$project->forceDelete()` cascading in the database. `unit_types`, `families` and `units` all cascade from `projects`, but `families.unit_type_id` and `units.unit_type_id` are `restrictOnDelete`. InnoDB checks RESTRICT immediately, row by row, while it walks the referencing foreign keys in order of constraint name. That order is `families_project_id_foreign` (families removed first) … `unit_types_project_id_foreign` … `units_project_id_foreign`, because `"unit_types_"` sorts before `"units_"`. So each unit type is deleted while its units still exist, and `units_unit_type_id_foreign` refuses. No model hook deletes children first (`Project::booted` only has `created`). The purge tests (`tests/UseCases/Platform/ArchiveTest.php:27-35,147-158`, `tests/Browser/Features/PlatformTest.php:70-98`) use projects with unit types and families but never units.
- **Failure scenario:** On staging, a superadmin archives the demo project "Las Acacias" (32 units), then opens Archived, types the name and confirms. `forceDelete` raises SQLSTATE[23000] 1451 on `units_unit_type_id_foreign`, the transaction rolls back and the person gets a 500. The project can never be deleted for good. The same happens when an admin deletes a project that has no history but already has units (`ProjectService::remove` → `purge`).
- **Recommended fix:** In `purge()`, delete the dependents explicitly inside the transaction: units, then families, then unit types, then the project. Or make both `unit_type_id` foreign keys cascade, if a unit type is only ever deleted along with its project; the app already guards deletion of in-use types. Add a purge test with units, families with members and a placed plan, run on MariaDB.
- **Verification note:** Read every foreign key referencing `projects`/`unit_types` across the migrations, the `Project` model hooks and the purge tests. The constraint-name ordering follows InnoDB's `dict_foreign_set` comparison.

### 05-04. Trusted `X-Forwarded-Host` allows host-header poisoning of password-reset links
- **Severity / category:** high / security
- **Location:** `bootstrap/app.php:26-37`, `deploy/switch/live.conf.template:23-31`, `app/Notifications/ResetPasswordNotification.php:22`
- **Problem:** `trustProxies` trusts all of 10/8, 172.16/12, 192.168/16 and 127/8, and honours `HEADER_X_FORWARDED_HOST`. The app's direct peer is the switch container (Docker network, trusted). The switch sets `Host`, `X-Real-IP`, `X-Forwarded-For` and `X-Forwarded-Proto`, and passes every other client header through unchanged, including `X-Forwarded-Host`. The host's front nginx is not in the repository, and its documented duties (`delivery.md:150-151`) do not include clearing the header. A stock `proxy_pass` config forwards it. No `trustHosts` or `URL::forceRootUrl` exists (grep of `app/`, `bootstrap/`). `ResetPasswordNotification` is not queued and builds its link with `route()`, so it uses the request's forwarded host.
- **Failure scenario:** An attacker sends `POST /forgot-password` with `email=owner@…` and `X-Forwarded-Host: evil.example`. The owner receives a genuine MTAV email whose button points to `https://evil.example/reset-password/<token>?email=…`. One click hands over the token, and the attacker resets the password and takes over the account, superadmin included.
- **Recommended fix:** Drop `HEADER_X_FORWARDED_HOST` from the trusted headers, or configure `$middleware->trustHosts()`. In the switch, set `proxy_set_header X-Forwarded-Host $host;`. In production, pin generated URLs with `URL::forceRootUrl(config('app.url'))`. Document that the host proxy must overwrite forwarded headers.
- **Verification note:** Confirmed the trust list, that the switch has no override, and that the reset notification is sync and uses `route()`. Exploitability depends on the host proxy config, which is outside the repository; the severity assumes nginx defaults.

### 05-05. Deployments page reports the wrong release after a deploy of a named commit, a `fresh`, or an aged-out rollback
- **Severity / category:** medium / correctness
- **Location:** `app/Services/Delivery/GitHub.php:73,112-150`, `app/Http/Controllers/Platform/DeploymentController.php:45-64`, `.github/workflows/production.yml:38-48`, `.github/workflows/fresh.yml:38-43`
- **Problem:** `live()` takes each successful deployment record's `sha` as the release that went live, and treats only `rollback.yml` runs as special. GitHub records an environment deployment at the run's `GITHUB_SHA`. The console dispatches production, rollback and fresh on `main`, so their records carry main's HEAD:
  - `production.yml` with a named commit, or with "the release staging runs" when that is older than main's HEAD, deploys a different commit than its record says.
  - `fresh.yml` redeploys whatever the environment ran, which may be a feature branch on dev.
  - A rollback is recognised only if its run is among the latest 30 runs of the whole repository (`per_page => 30`, every workflow on every branch). Once it ages out, it reads as "deployed main HEAD".
- **Failure scenario:** A deployer redeploys production at an older commit `abc1234` from the console, which the page invites ("unless a commit of main is named", `Deployments.tsx:279`). The page then shows production on main's HEAD, "0 commits behind", while it actually runs `abc1234`. The same happens after "Start dev over" on a feature branch, or after a production rollback followed by about 30 pushes anywhere. An operator trusting the page skips a needed deploy or rolls back in the wrong direction.
- **Recommended fix:** Read the live release from the gate's `status` (`live=<slot>:…:sha-<commit>`), for example exposed to the app by a small status file or endpoint, rather than replaying GitHub records. Failing that, record the real commit in the deployment payload or a job output, treat `fresh` like a rollback (same release), and resolve a status's run with `GET actions/runs/{id}` instead of relying on the latest 30.
- **Verification note:** Traced the dispatch refs in `DeploymentController`, the commit selection in `production.yml`/`fresh.yml`, and the replay in `live()`.

### 05-06. A superadmin can demote the owner (and any superadmin), then block or delete them
- **Severity / category:** medium / security
- **Location:** `app/Http/Controllers/Platform/UserController.php:68-73,97-108`, `resources/js/pages/Platform/Superadmins.tsx:78-88`, `routes/web.php:270-299`
- **Problem:** `superadmin()` only refuses revoking yourself. `is_owner` is never checked, and the page offers "Remove the role" on the owner's row. After demotion, `ensureNotProtected()` no longer protects them, because it checks `isSuperadmin()` and not `is_owner`. Self-deletion refuses the owner (`ProfileController.php:82`), but deletion by another superadmin does not. No console route uses `password.confirm`: granting or revoking the role, deleting users or projects, and deploying all go through on a session alone.
- **Failure scenario:** A rogue superadmin, or a hijacked superadmin session, revokes the owner's role and then deletes the owner from Users. From then on, `ErrorLog` finds no `is_owner` user (`ErrorLog.php:53`), so nobody is mailed about new errors, and restoring control needs shell access (`mtav:superadmin`). The same session can also grant superadmin to any account with one request.
- **Recommended fix:** Treat `is_owner` as protected in `superadmin()`, `block()` and `destroy()`, and hide the button on the owner's row. Put `password.confirm` on grant/revoke, user and project deletion, and the deployment POSTs.
- **Verification note:** Read the controller, `User::isSuperadmin()` (plain `is_superadmin`), the routes and the page; no other code checks `is_owner` in this path.

### 05-07. Error monitor merges all vendor-thrown errors into one entry and stops alerting
- **Severity / category:** medium / correctness
- **Location:** `app/Services/ErrorLog.php:28-37,53`
- **Problem:** The fingerprint is `class|file|line` of the throw site. Most real failures are thrown at fixed lines inside the framework: `QueryException` in `Illuminate/Database/Connection.php`, `RequestException`/`ConnectionException` in the HTTP client, `ModelNotFoundException`, and so on. Every distinct SQL bug in the app therefore shares one fingerprint. Each repeat overwrites `message` with the newest one, and the owner is mailed only the first time.
- **Failure scenario:** A missing-column bug in the forum is logged and the owner is mailed. A week later a different query in the lottery code starts failing. It increments the old entry and replaces its message, so the forum error's details are lost. No email goes out, and the regression goes unnoticed. Every GitHub and Anthropic HTTP failure collapses into one entry in the same way.
- **Recommended fix:** Fingerprint on the class plus the first stack frame under `app/` (skipping `vendor/`), and for `QueryException` add the SQL template without bindings. Keep the first message, or a few samples, instead of overwriting.
- **Verification note:** Read `capture()`; the update branch returns before any notification.

### 05-08. CI and image builds depend on mutable tags: actions, base images and the pnpm tarball are not pinned or verified
- **Severity / category:** medium / security
- **Location:** `.github/workflows/release-image.yml:38,57,60`, `.github/workflows/pipeline.yml:37-52`, `deploy/Dockerfile:12,17,31,43`, `deploy/compose.yml:12,31`, `deploy/host/gate:129`
- **Problem:** The release-image job holds a `packages: write` token and runs third-party actions referenced by movable tags: `docker/login-action@v3`, `docker/setup-buildx-action@v3`, `docker/build-push-action@v6`. `shivammathur/setup-php@v2`, `pnpm/action-setup@v6.1.0` and `actions/*@v5` are tag-pinned as well. Base images are pinned by tag only: node, frankenphp, composer, mariadb, nginx-unprivileged, and alpine, which the gate runs as root. The Dockerfile pipes a pnpm tarball from GitHub releases straight into `tar` with no checksum (`Dockerfile:17`).
- **Failure scenario:** An upstream tag is moved to a malicious commit, as in the tj-actions/changed-files incident. The next main build runs it with registry write access and publishes a backdoored release image, which production then deploys. A tampered pnpm release asset does the same inside the image build.
- **Recommended fix:** Pin every action to a full commit SHA and let Dependabot or Renovate bump them. Pin base images by `@sha256:` digest. Verify the pnpm tarball against a known SHA-256, or install pnpm through corepack with a hash.
- **Verification note:** Listed every `uses:`, `FROM` and `image:` line in the repository; none uses a SHA or digest.

### 05-09. Nothing prunes the activity log, error log, database cache or failed jobs; the activity page counts the whole table
- **Severity / category:** medium / performance
- **Location:** `routes/console.php:1-8`, `bootstrap/app.php:13-19`, `deploy/slot.yml:26-61`, `deploy/env.example:41`, `app/Http/Controllers/Platform/ActivityController.php:27-33`, `app/Models/Activity.php:35-40`, `database/migrations/2026_10_30_000002_create_activity_log.php:14-29`
- **Problem:**
  - **No scheduler:** `routes/console.php` holds only `inspire`, nothing calls `withSchedule`, and `slot.yml` has no scheduler role, so no prune ever runs.
  - **Activity log:** it gets a row, with the IP, for every model change, sign-in, sign-out and failed sign-in, and keeps them forever. Anonymous clients can add failed sign-ins.
  - **Cache:** `CACHE_STORE=database` deletes an expired row only when that key is read again, so per-IP/email throttle keys accumulate.
  - **Other tables:** `failed_jobs` and `error_log` grow the same way.
  - **Activity page:** `/admin/activity` uses `paginate()`, which runs a `COUNT(*)` over the filtered table on every view. `?q=` is a leading-wildcard `LIKE` on two unindexed columns, and `kind=` filters on unindexed `action`.
- **Failure scenario:** After a year of use plus a few credential-stuffing bursts against `/login`, `activity_log` holds millions of rows. Each open of the activity page runs a full count and, when searching, a table scan. That takes seconds on the MariaDB the members share, and IP addresses are kept indefinitely.
- **Recommended fix:** Add a scheduler, for example a `schedule:work` service that only the live slot runs. Make `Activity` and `ErrorEntry` `Prunable` with a retention period (failed sign-ins shorter), and schedule `model:prune`, `queue:prune-failed` and a sweep of expired `cache` rows. Use `simplePaginate()` for the activity list and add an index on `action`.
- **Verification note:** Searched `app`, `bootstrap` and `routes` for `schedule`/`Schedule::` (none) and read `slot.yml`'s services.

### 05-10. Deployments page makes up to ~41 sequential GitHub calls; GitHub outages surface as 500s
- **Severity / category:** low / performance
- **Location:** `app/Services/Delivery/GitHub.php:33-49,61-62,115-116,144,155,179`, `app/Http/Controllers/Platform/DeploymentController.php:40,68-74`
- **Problem:**
  - **Sequential calls:** `environments()` makes, one after another, a runs call, then for each of 3 environments one deployments list, up to 10 status calls, one commit and one compare. That is up to 40 blocking calls with a 10 s timeout each, recomputed every 30 s, plus `main()`. All deferred props resolve in one request.
  - **No error handling in `dev()`:** it validates against `branches()`, so a `ConnectionException` or `RequestException` there escapes as a 500.
  - **Narrow catch in `dispatch()`:** it catches only `RequestException`, not `ConnectionException` (they are siblings under `HttpClientException`).
  - **Branch limit:** `branches()` reads only the first 100 branches.
- **Failure scenario:** With GitHub answering at about 2 s per call, the deferred request outlasts the front proxy's timeout (nginx default 60 s; the switch allows 120 s) and the page shows nothing. With GitHub unreachable, "Deploy dev" or "Deploy production" shows the generic error page instead of the "GitHub refused" message. A branch beyond the first 100 is rejected as invalid.
- **Recommended fix:** Fetch statuses concurrently with `Http::pool`, or read live releases from the gate (see 05-05). Catch `HttpClientException` in `dispatch()` and around `branches()` in `dev()`. Validate a branch with `GET branches/{name}` instead of the list.
- **Verification note:** Counted the calls in `live()`/`environments()` and checked the exception types caught.

### 05-11. Error log stores reset tokens and personal data from URLs and SQL messages, shows them to all superadmins and emails them
- **Severity / category:** low / security
- **Location:** `app/Services/ErrorLog.php:34,43,46,48`, `app/Notifications/NewErrorNotice.php:35`, `app/Http/Controllers/Platform/ErrorController.php:17`
- **Problem:** The full request URL is stored, including `reset-password/{token}?email=…` and invitation tokens, along with the exception message and trace. `QueryException` messages include the bound values: emails, phone numbers, message text. All of it is serialised whole to every superadmin, and the message is mailed in plain text to the owner.
- **Failure scenario:** The database hiccups while a superadmin opens their password-reset link. The entry now holds a valid token plus their email, and any other superadmin can use it within the token's lifetime to take over that account. A failing insert of someone's chat message lands in the owner's mailbox.
- **Recommended fix:** Redact the token segments of known secret routes (`password.reset`, invitations, signed URLs) before storing the URL. Store SQL without bindings (`QueryException::getSql()`). Mail only the class and location, with a link to the console entry.
- **Verification note:** Read `capture()` and the notice; no redaction exists.

### 05-12. Pre-deploy database dumps are written world-readable on the host
- **Severity / category:** low / security
- **Location:** `deploy/host/deploy:94-101`, `deploy/host/setup:112-136`
- **Problem:** The deploy runs as root under sudo's default umask of 022. `mkdir -p $dir/backups` and `gzip > $dump` therefore create a 755 directory and 644 dumps of the whole database: password hashes, emails, phones, chat. The last 20 per environment are kept. Neither setup nor deploy sets permissions on `/mtav/<env>`.
- **Failure scenario:** Any other local account on the host, such as the interactive `x` account the warden uses (05-16) or a compromised service, reads `/mtav/production/backups/*.sql.gz` and gets a full copy of production data.
- **Recommended fix:** Run `umask 077` at the top of the gate and deploy scripts, or chmod the backups directory to 700 and each dump to 600. Have setup create `/mtav/<env>` as 700, owned by root.
- **Verification note:** No `umask` or `chmod` appears in `deploy/host/*`.

### 05-13. A deploy can kill a running lottery: the worker's grace period is shorter than the lottery job's timeout
- **Severity / category:** low / correctness
- **Location:** `deploy/slot.yml:54-58`, `deploy/host/deploy:140`, `app/Jobs/RunLottery.php:24-32,67-73`, `config/queue.php:45`
- **Problem:** Going live stops the old slot's worker with `stop_grace_period: 2m`. `RunLottery` may legitimately run up to 600 s (`tries = 1`), and each GLPK solve is bounded at 30 s per unit type (`config/lottery.php:65`). After 2 minutes Docker sends SIGKILL, so the job neither finishes nor runs `failed()`. The row stays reserved until `retry_after` (660 s) expires, and only then does the new worker mark it failed.
- **Failure scenario:** An admin starts the lottery of a project with many unit types, and a minute later a deploy goes live. The solve passes 2 minutes and is killed. For about 11 minutes every member sees the lottery as running, and then it fails with "execution failed".
- **Recommended fix:** Set the worker's `stop_grace_period` above the longest job timeout (e.g. 11m), or have the deploy wait until no `RunLottery` is reserved before stopping the old worker.
- **Verification note:** Compared `slot.yml`, the job's timeout and `failed()`, and the queue's `retry_after`. Whether a real solve exceeds 2 minutes depends on the project's size.

### 05-14. Array query parameters crash the console's list pages with a TypeError
- **Severity / category:** low / correctness
- **Location:** `app/Http/Controllers/Platform/ActivityController.php:28`, `app/Http/Controllers/Platform/ProjectController.php:32`, `app/Http/Controllers/Platform/UserController.php:22`, `app/Models/Activity.php:35`, `app/Models/Project.php:275`, `app/Models/User.php:206`
- **Problem:** `$request->q` is passed straight to `scopeSearch(Builder, string $q)`. With `?q[]=x` it is an array, which PHP never coerces to a string.
- **Failure scenario:** Opening `/admin/activity?q[]=a` returns a 500 and adds an entry to the error log, instead of the parameter being ignored.
- **Recommended fix:** Read the term with `$request->string('q')->toString()`, or validate `q` as `nullable|string|max:100`.
- **Verification note:** All three `scopeSearch` signatures type the term as `string`.

### 05-15. Errors page: 30 identical "Solved" buttons delete entries with no accessible context
- **Severity / category:** low / accessibility
- **Location:** `resources/js/pages/Platform/Errors.tsx:54-63,68-75`
- **Problem:** Each card's button is labelled only "Solved", deletes the entry at once with no confirmation, and is not tied to the card's title. The cards have no headings to navigate by. The trace toggle's label does change ("Show/Hide the trace"), but it lacks `aria-expanded`/`aria-controls`.
- **Failure scenario:** A screen-reader user tabbing through the page hears "Solved, button" thirty times, with no way to tell which error each one dismisses, and deletes the wrong entry.
- **Recommended fix:** Label each button with its error (e.g. `aria-label` "Mark {class} solved") or point `aria-describedby` at the card's title. Make the class a heading, and add `aria-expanded` to the trace toggle.
- **Verification note:** Read the component markup.

### 05-16. Production host IP and a Docker-capable SSH login are committed in the repository
- **Severity / category:** low / security
- **Location:** `dev/warden/sources.mjs:8,122`
- **Problem:** `const SERVER = process.env.MTAV_SERVER ?? 'x@179.27.99.138'` publishes the host's IP and the name of an interactive account that runs `docker ps` over SSH. That account is in the docker group, which is root-equivalent.
- **Failure scenario:** Anyone reading the repository has the exact target and username for SSH brute force against a root-equivalent account. If the sites sit behind a proxy or CDN, the origin is also exposed.
- **Recommended fix:** Remove the default and require `MTAV_SERVER` from the developer's environment. Make sure that account accepts keys only.
- **Verification note:** Read the constant and its single use at line 122.

## Checked, nothing to report
- **Console authorization:** the `/admin` group sits behind `EnsureSuperadmin`. Deployments need `can:deploy` (superadmin, `DEPLOYERS` allow-list and token) plus `throttle:6,1`. Platform settings are validated (`between:0,300`).
- **Command and input injection:** the gate validates the action, environment (regex), commit (40-hex) and option. Workflow inputs reach the shell via `env:` (`production.yml:32`) or `choice` inputs, which the gate re-validates. Console dispatch inputs are allow-listed. Sudo keeps only `SSH_ORIGINAL_COMMAND`, so `MTAV_HOME` cannot be injected.
- **Destructive-operation guards:** `fresh` is refused on production by the gate, by the deploy script and by `DB::prohibitDestructiveCommands`. The seeder refuses public seeding without `DEMO_PASSWORD`. Backups use a separate read-only key.
- **Secrets in the repository:** `.env.template` and `deploy/env.example` carry no real secrets. The CI deploy script writes the key to a 600 temp dir removed on exit, with `StrictHostKeyChecking=yes`.
- **Workflow permissions:** top-level `contents: read` everywhere. Fork PRs run only checks and tests, with no `pull_request_target`.
- **Hot-path indexes:** projects, memberships, invitations, forum, chat, notifications, lottery runs and `error_log.last_seen_at` are indexed for their list queries (except `activity_log.action`, see 05-09).
- **Blue/green rollback and image cleanup:** `docker rmi` without `-f` cannot remove images in use by other environments' containers.
