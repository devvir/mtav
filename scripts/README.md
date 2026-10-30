# Scripts

Tools run by hand, outside the app.

| Script | What it does | How |
|---|---|---|
| `guide-shots/shots.mjs` | the pictures of the illustrated manuals, from a seeded app | [guide-shots/README.md](guide-shots/README.md) |
| `offline-check.mjs` | working offline, checked in a real browser (the browser suite cannot cut the network) | `BASE=http://127.0.0.1:8010 node scripts/offline-check.mjs`, against a seeded app with built assets |
| `benchmark_analysis/` | statistics and charts of the GLPK benchmarks | [benchmark_analysis/README.md](benchmark_analysis/README.md) |

Backups have their own script, beside the deploy: `deploy/backup/pull`
([documentation/technical/data.md](../documentation/technical/data.md)).
