// Copilot - pending review

import { addMatchImageSnapshotCommand } from '@simonsmith/cypress-image-snapshot/command';
import type { CypressImageSnapshotOptions } from '@simonsmith/cypress-image-snapshot/types';

// Register matchImageSnapshot command with default options
addMatchImageSnapshotCommand({
  failureThreshold: 0.03, // Allow 3% difference
  failureThresholdType: 'percent',
  capture: 'viewport', // Only capture the app viewport, not Cypress UI
  customSnapshotsDir: 'tests/e2e/snapshots', // Flatten snapshot directory
  customDiffDir: 'tests/e2e/snapshots/__diff_output__',
});

// Custom command to login as different user types
// Maps to universe.sql fixture users:
// - superadmin: User #1 (no projects)
// - admin1: User #11 (manages Project #1)
// - admin2: User #12 (manages Projects #2, #3)
// - member: User #102 (Family #4)
Cypress.Commands.add('loginAs', (userType: 'superadmin' | 'admin1' | 'admin2' | 'member') => {
  const credentials = {
    superadmin: { email: 'superadmin1@example.com', password: 'password' },
    admin1: { email: 'admin11@example.com', password: 'password' },
    admin2: { email: 'admin12@example.com', password: 'password' },
    member: { email: 'member102@example.com', password: 'password' },
  };

  const user = credentials[userType];

  cy.visit('/login');
  cy.get('#email').type(user.email);
  cy.get('#password').type(user.password);
  cy.get('button[type="submit"]').click();

  // Wait for redirect after login
  cy.url().should('not.include', '/login');
});

// Override matchImageSnapshot to wait for Inertia deferred content
Cypress.Commands.overwrite('matchImageSnapshot', (originalFn, nameOrOptions?: string | CypressImageSnapshotOptions) => {
  // Wait for deferred Inertia content (SkeletonCard with animate-pulse)
  cy.get('body').then(($body) => {
    if ($body.find('.animate-pulse').length > 0) {
      cy.get('.animate-pulse', { timeout: 3000 }).should('not.exist');
    }
  });

  // Default options
  const defaults: CypressImageSnapshotOptions = {
    failureThreshold: 0.03, // Allow 3% difference
    failureThresholdType: 'percent',
    capture: 'viewport', // Only capture the app viewport, not Cypress UI
  };

  // Type assertion to handle the overloaded signature properly
  type MatchImageSnapshotFn = {
    (nameOrOptions?: string | CypressImageSnapshotOptions): Cypress.Chainable;
  };

  const fn = originalFn as unknown as MatchImageSnapshotFn;

  // Handle the single parameter overload
  // matchImageSnapshot(nameOrOptions?: string | CypressImageSnapshotOptions)
  if (typeof nameOrOptions === 'string') {
    // String name only - pass through without modification
    return fn(nameOrOptions);
  } else if (nameOrOptions) {
    // Options object - merge with defaults
    return fn({ ...defaults, ...nameOrOptions });
  } else {
    // No arguments - use defaults
    return fn(defaults);
  }
});
