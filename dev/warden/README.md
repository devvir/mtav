# The warden

MTAV from above, on a developer's machine: everything at a glance, deploying and running it, and its logs. A page made
of MTAV's own design system, on three tabs:

- **Overview**: this machine, the sites, the local stacks, the repository and its pipelines, the local app's figures,
  the Claude accounts' usage.
- **Deployment**: where each environment runs and what (its live release, the one before, whether it is behind its
  branch), and doing something with it (deploy, roll back, start over), through the same GitHub workflows as ever;
  AWS's servers (state, memory, disk, the data disk, the certificate; start and stop); the infrastructure (Check runs
  OpenTofu's plan, Apply makes exactly that plan); credits and this month's spend; the latest backups, and one taken
  now. Each action's output is followed live.
- **Logs**: the latest lines of any container of a deployed environment.

```sh
./mtav warden        # builds the page (a few seconds), then http://localhost:9190
```

It needs this machine's `docker`, `gh`, `aws` (the `mtav` profile, MTAV's AWS project) and `tofu`. `MTAV_SERVERS`
names the server before AWS to read over `ssh` (`user@host`, comma separated), from the environment or the
repository's `.env`; without it, that part says it is not set up, and the others go on.

It sits on top of the app rather than in it: it is left out of every image, so no deployment can run it. It listens on
this machine alone, reads nothing with nobody looking, and does something only when its own page asks (a header no
other page can send it). Each source (`sources.mjs`) has its own pace and fails on its own; each action
(`actions.mjs`) is checked before it runs and runs as a job (`jobs.mjs`); AWS is reached through `aws.mjs` (no SSH:
commands on a server go through Systems Manager). Claude's usage comes from the readings the accounts' folder already
holds: it never touches a login.

| Part | What it is |
| --- | --- |
| `server.mjs` | the page, the readings (`/api`), the actions (`/api/actions/<name>`) and their jobs (`/api/jobs/<id>`) |
| `sources.mjs` | what it watches, each on its own pace |
| `actions.mjs` | what it does when asked |
| `jobs.mjs` | an action's output, kept as it comes |
| `aws.mjs` | AWS as the `mtav` profile; a command on a server through Systems Manager |
| `web/` | the page (React, Mantine, MTAV's design system), built by Vite into `dist/` |

To watch something more: a source in `sources.mjs`, its part in `web/views/`. To do something more: an action in
`actions.mjs`, its button in `web/views/`.
