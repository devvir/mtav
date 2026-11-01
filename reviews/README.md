# Review of the app (main @ 42f3f13), 2026-11-01

An end-to-end review of `main`, split into eight areas. Each area was reviewed against these dimensions: security, correctness, performance, good practices, accessibility, i18n and tests. A second, independent pass re-checked every finding against the code: false positives were dropped and severities corrected. This was a static review, run without vendor/ or node_modules/ installed. No code was changed.

## Read this first

- **The review covers `main` only.** Open Lottery is not on `main` yet, so area 03 covers the MTAV lottery as `main` has it.
- **`feature/variants` renamed parts of the lottery engine and already fixes some of these findings.** Map each finding across to its new name, and skip it if it is already fixed there.
- Findings use the IDs `NN-MM`, where NN is the area number. Every finding gives a file:line, a concrete failure scenario and a recommended fix in words.

## Totals

| Area | Report | Critical | High | Medium | Low |
|---|---|---|---|---|---|
| 01 | [Authentication, accounts, invitations & authorization](01-auth-access.md) | 0 | 1 | 4 | 12 |
| 02 | [Projects, members, families, directory & admin tools](02-projects-members.md) | 0 | 0 | 3 | 13 |
| 03 | [Lottery engine](03-lottery.md) | 0 | 3 | 3 | 8 |
| 04 | [Forum, chat, media, notifications, push & assistant](04-communication-media.md) | 0 | 0 | 8 | 13 |
| 05 | [Platform console, operations, data layer & CI/deploy](05-platform-ops.md) | 1 | 3 | 5 | 7 |
| 06 | [Frontend pages & layouts](06-frontend-pages.md) | 0 | 0 | 3 | 17 |
| 07 | [Frontend components, design system, lib & tours](07-frontend-foundation.md) | 0 | 0 | 6 | 13 |
| 08 | [Test suite & quality gates](08-tests-quality.md) | 0 | 1 | 4 | 6 |
| | **Total (134)** | **1** | **8** | **36** | **89** |

The lottery tie-breaker issue appears in both 03 and 08, from two angles: the bug itself, and the missing tests.

## Fix first

1. **Critical. Any branch's code can run as root on the production host** (05). The dev deploy key reaches the shared host, and the gate runs the deploy script and compose files taken from the image it is given. `deploy/host/gate:96-101`
2. **High. The lottery has no real randomness** (03, 08). The random tie-break is rounded away by `%.3f` and is a constant per family anyway, so ties always resolve by row order. The tests only check integer ranks. `app/Services/Lottery/Solvers/Glpk/DataGenerator.php:117`
3. **High. The max-min fairness guarantee is lost in unbalanced unit types** (03). Pruning drops units the optimum needs when there are spare units, and mock units make the max-min phase meaningless when units run short. `app/Services/Lottery/Solvers/Glpk/SpecBalancer.php:32`, `:56`
4. **High. Password reset or change leaves every other session signed in** (01). Together with changing the email needing no password, this allows an account takeover. `app/Http/Controllers/Auth/NewPasswordController.php:58`
5. **High. A trusted `X-Forwarded-Host` allows host-header poisoning of password-reset links** (05). `bootstrap/app.php:26-37`
6. **High. Deleting a project for good fails once it has units** (05). A RESTRICT foreign key sits beside the cascade. `database/migrations/2026_10_27_000001_create_projects_tables.php:39`
7. **High. Mutable image tags, and every branch can write to the registry** (05). Production can be handed a poisoned release.

## Notable medium findings

- CSV exports are open to spreadsheet formula injection (02-01, `Manage/ExportController.php:63`).
- Ask/withdraw loops let any user spam admins by push and family members by email (02-02).
- Archived media escapes the storage quota (04, `MediaService.php:154`).
- Forum replies keep notifying and pushing to people who left the project (04).
- One malformed push subscription can stop push delivery for a whole project (04).
- Sign-out leaves chat cache, websocket subscriptions and the Web Push subscription in place. On a shared device, the next person sees the previous user's chats and their push notifications keep arriving (07).
- A lottery ranking change waiting in the debounce is lost on navigation (06, `Member/Lottery.tsx:89`).
- Accessibility: no focus move or announcement after client-side navigation and no skip link; the landing carousel announces itself every 6 s (06).
- Any superadmin can demote the deployment's owner, then block or delete them (01).
- The superadmin-protection test never targets another superadmin, and several sensitive routes lack denial tests (08).

## Strengths noted across areas

The reviewers confirmed that authorization between projects holds: scoped bindings, policies and tenancy checks were consistent. Uploads are content-checked and served from a private disk. User content is rendered as React text, with no XSS found. All 1,009 i18n keys used in code are present in every locale.
