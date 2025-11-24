// Copilot - pending review
import './cypress-commands';

/* eslint-disable @typescript-eslint/no-namespace */
declare global {
  namespace Cypress {
    interface Chainable {
      /**
       * Take a visual snapshot of the current page (body element only, excludes Cypress UI)
       * @param name - Name of the snapshot
       * @example cy.matchPageSnapshot('dashboard-page')
       */
      matchPageSnapshot(name: string): Chainable;

      /**
       * Login as a specific user type
       * @param userType - 'superadmin' (no projects), 'admin1' (1 project), 'admin2' (2 projects), or 'member'
       */
      loginAs(userType: 'superadmin' | 'admin1' | 'admin2' | 'member'): Chainable;

      /**
       * Wait for Inertia deferred content to finish loading (animate-pulse class to disappear)
       */
      waitForDeferred(): Chainable;
    }
  }
}
