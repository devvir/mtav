// Copilot - pending review
import './cypress-commands';

/* eslint-disable @typescript-eslint/no-namespace */
declare global {
  namespace Cypress {
    interface Chainable {
      /**
       * Take a visual snapshot of the current page
       * @param name - Name of the snapshot
       * @example cy.matchImageSnapshot('dashboard-page')
       */
      matchImageSnapshot(name: string): Chainable;

      /**
       * Login as a specific user type
       * @param userType - 'superadmin' (no projects), 'admin1' (1 project), 'admin2' (2 projects), or 'member'
       */
      loginAs(userType: 'superadmin' | 'admin1' | 'admin2' | 'member'): Chainable;
    }
  }
}
