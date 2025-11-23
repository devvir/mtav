# Cypress Visual Snapshot Testing

This folder contains Cypress E2E tests that take visual snapshots of all Inertia view routes.

## Structure

- `Views/` - Root tests for non-entity routes (dashboard, auth, settings, etc.)
- `Views/entities/` - Tests for resource/entity routes (admins, members, families, etc.)
- `support/` - Cypress support files and custom commands

## Running Tests

### First Run (Generate Snapshots)

```bash
# Run all tests and generate baseline snapshots
./mtav npm run test:e2e

# Or run in headless mode
./mtav npx cypress run
```

On the first run, Cypress will:
1. Visit each route
2. Take a screenshot
3. Save it as the baseline snapshot in `resources/js/tests/__image_snapshots__/`

### Subsequent Runs (Compare Snapshots)

When you run tests again, Cypress will:
1. Visit each route
2. Take a new screenshot
3. Compare it against the baseline
4. **FAIL** if there are differences > 3%

### Accepting New Snapshots

If visual changes are intentional (e.g., after a UI update), update the baselines:

```bash
# Update all snapshots
./mtav npx cypress run --env updateSnapshots=true

# Or delete the __image_snapshots__ folder and re-run tests
rm -rf resources/js/tests/__image_snapshots__
./mtav npm run test:e2e
```

## Test Files

### Entity Routes (Views/entities/)

- `admins.cy.ts` - Admin CRUD views
- `audios.cy.ts` - Audio upload views
- `documents.cy.ts` - Document views
- `events.cy.ts` - Event CRUD views
- `families.cy.ts` - Family CRUD views
- `gallery.cy.ts` - Gallery view
- `logs.cy.ts` - Log views
- `media.cy.ts` - Media CRUD views
- `members.cy.ts` - Member CRUD views
- `plans.cy.ts` - Plan views
- `projects.cy.ts` - Project CRUD views
- `unit_types.cy.ts` - Unit Type CRUD views
- `units.cy.ts` - Unit CRUD views

### Other Routes (Views/)

- `auth.cy.ts` - Login, password reset, invitation
- `contact.cy.ts` - Contact form
- `dashboard.cy.ts` - Main dashboard
- `dev.cy.ts` - Development tools (superadmin only)
- `documentation.cy.ts` - FAQ and guide
- `lottery.cy.ts` - Lottery management
- `settings.cy.ts` - User settings (profile, password, appearance)

## Custom Commands

### `cy.loginAs(userType)`

Logs in as a specific user type:

```typescript
cy.loginAs('superadmin'); // superadmin@example.com
cy.loginAs('admin');      // admin@example.com
cy.loginAs('member');     // member@example.com
```

### `cy.matchImageSnapshot(name)`

Takes a visual snapshot and compares against baseline:

```typescript
cy.matchImageSnapshot('dashboard-page');
```

## Configuration

Visual snapshots are configured in `cypress.config.ts`:

- **Base URL**: `http://localhost`
- **Viewport**: 1280x720
- **Failure Threshold**: 3% difference allowed
- **Screenshots**: Saved to `resources/js/tests/screenshots/`
- **Videos**: Disabled by default

## Notes

- Tests assume seeded database with specific IDs (ID 1 for most entities, ID 2 for admins)
- Some routes (like invitation) may require additional setup
- Dev routes require superadmin access
- Snapshots include wait time for animations/loading (1000ms)

## Troubleshooting

**Tests fail with "Cannot find name 'cy'"**:
- TypeScript may need Cypress types. They should be auto-detected from `cypress` package.

**Snapshots don't match but look identical**:
- Try increasing the failure threshold in `support/commands.ts`
- Check for dynamic content (dates, random IDs) that change between runs

**Login fails**:
- Ensure database is seeded with test users
- Check that passwords match in `support/commands.ts`
