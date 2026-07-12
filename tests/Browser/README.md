# E2E Journey Suite (Playwright)

The last safety net before a deployment: a handful of long, start-to-finish
**journeys** that walk the app's main use cases the way real users do. Run with
`mtav e2e` (or `mtav e2e --filter '<journey name>'` for one journey). Slow is
fine — this suite is about depth, not cadence.

Infrastructure: the suite runs in the dedicated `mtav-e2e` composition
(`docker/e2e/`) — one container with PHP + node + Playwright, where the Pest
browser plugin serves the Laravel app **in-process**. That means journeys can
freely mix browser actions with Eloquent state, `actingAs()`, and event
listeners (see `tests/Helpers/browser.php`). Each journey reloads the
`universe.sql` fixture (`tests/Fixtures/UNIVERSE.md`), and browser tests are
**not** transaction-wrapped — journeys own their data via unique values.

## Covered flows

### Journey 1 — Provisioning (`ProvisioningJourneyTest`)
- Superadmin login through the **real login form** (the one place the login UI
  itself is exercised; other journeys use `actingAs()`).
- Superadmin creates a **project**, inviting its first **admin** inline
  (`new_admin_*` fields on the project form).
- The invitation email link is captured (via the `UserRegistration` event —
  the plaintext token never touches the DB) and visited: the invited admin
  **completes registration** and lands authenticated in the new project.

### Journey 2 — Admin setup (`AdminSetupJourneyTest`)
- As a single-project admin (#11): create a **unit type**, a **unit** of that
  type, and a **family** assigned to that type.
- Invite a **member** into the new family and a **fellow admin** (single-project
  admins get their project auto-filled on hidden selects — a real bug found and
  fixed by this journey).
- The invited member completes registration and lands on the dashboard;
  membership in the family is asserted in the DB.

### Journey 3 — Member life (`MemberJourneyTest`)
- As member #102: edit **profile** (name, about), change **password** — proven
  by a real logout (through the user menu) and **re-login with the new
  password** — and change **email** (asserts the verification-sent notice and
  the pending `new_email`).
- Community browsing with async-aware waits: **families** index/show,
  **members** index, **gallery**, **events**, and the **lottery** page, each
  smoke-checked (`assertNoSmoke`).

### Journey 4 — Lottery (`LotteryJourneyTest`)
- As admin #11: **reschedule** the lottery (datetime + description, flash
  asserted, DB asserted).
- Move the scheduled date into the past (Eloquent) and **execute for real**
  with the production **GLPK solver**: the fixture's unit/family mismatch
  triggers the warning path, so the journey covers both typed confirmations
  (`EXECUTE`, then `CONFIRM` acknowledging the mismatch).
- Reaches visible results ("Lottery Completed!"), event soft-deleted, units
  assigned in the DB.
- Then as **superadmin**: **invalidate** the execution (typed `INVALIDATE`) —
  the lottery event is restored and every unit assignment cleared.

### Journey 5 — Authorization boundaries (`AuthorizationJourneyTest`)
The access-control safety net, three deflections asserted by landing path:
- **Guest** → any protected page → bounced to `/login`.
- **Member** → admin-only routes (`admins.create`, `unit_types.create`) →
  silent redirect to the dashboard (policy denial; see `bootstrap/app.php`).
- **Invited-but-unregistered user** → any route → forced back to
  `/invitation` (`HandleInvitedUsers`).

### Journey 6 — Preferences drive the lottery (`PreferencesJourneyTest`)
The domain core — the member preferences UI and the ranking→outcome loop:
- **Member #136** (family 13, project 2) reorders their family's unit
  preferences via the ranking controls (`Move down` on the top card); the
  async save is asserted in the `unit_preferences` DB order.
- Family 14 is given the opposite top choice, so the two families compete
  with **distinct** first picks.
- **Admin #12** runs the lottery (GLPK, mismatch confirmation) and the
  assignment **honors the rankings**: family 13 gets its top pick (Unit 5),
  family 14 gets Unit 4 — a deterministic outcome asserted in the DB.

## Conventions

- **Selectors**: resource forms (FormService-generated) expose
  `input[name=…]` / `select[name=…[]]`; settings/login/lottery forms use
  `#id`; modals are scoped with `div[role="dialog"] …:has-text("…")`.
- **Async everywhere**: lists and dashboards load deferred — always
  `waitForText(…)` (or wait for `.animate-pulse` to clear) before asserting.
- **Flash messages** are the deterministic post-submit signal — assert the
  exact texts from `lang/en/*.php`.
- **Failure screenshots** land in `tests/Browser/Screenshots/` (gitignored).
- Adding a journey: cover a user story end to end, keep it self-contained,
  and document it here.
