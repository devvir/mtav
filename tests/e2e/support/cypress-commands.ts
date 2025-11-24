/// <reference types="cypress" />

import { addMatchImageSnapshotCommand } from '@simonsmith/cypress-image-snapshot/command';

// Register matchImageSnapshot command with default options
addMatchImageSnapshotCommand({
  failureThreshold: 0.03, // Allow 3% difference
  failureThresholdType: 'percent',
  customSnapshotsDir: 'tests/e2e/snapshots', // Flatten snapshot directory
  customDiffDir: 'tests/e2e/snapshots/__diff_output__',
});

// Custom command to login as different user types (see universe.sql fixture):
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

  // CSRF is disabled for /login in testing, so we can POST directly
  cy.request({
    method: 'POST',
    url: '/login',
    form: true,
    body: {
      email: user.email,
      password: user.password,
    },
    followRedirect: false,
  }).then((response) => {
    // Verify successful redirect to home
    expect(response.status).to.eq(302);
    expect(response.headers.location).to.eq('http://nginx');
  });
});

// Helper to wait for Inertia deferred content before taking snapshots
Cypress.Commands.add('waitForDeferred', () => {
  cy.get('body').then(($body) => {
    if ($body.find('.animate-pulse').length > 0) {
      cy.get('.animate-pulse', { timeout: 3000 }).should('not.exist');
    }
  });
});