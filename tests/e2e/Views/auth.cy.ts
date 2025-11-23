// Copilot - pending review

describe('Authentication Routes - Visual Snapshots', () => {
  const allRoutes = [
    { path: '/login', name: 'login' },
    { path: '/forgot-password', name: 'forgot-password' },
    { path: '/reset-password/test-token', name: 'reset-password' },
    { path: '/invitation?token=test-token', name: 'invitation' },
    { path: '/confirm-password', name: 'confirm-password' },
    { path: '/verify-email', name: 'verify-email' },
  ];

  describe('As Guest', () => {
    allRoutes.forEach(({ path, name }) => {
      it(`${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`auth-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    allRoutes.forEach(({ path, name }) => {
      it(`${name} - should match snapshot (authenticated)`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`auth-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    allRoutes.forEach(({ path, name }) => {
      it(`${name} - should match snapshot (authenticated)`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`auth-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    allRoutes.forEach(({ path, name }) => {
      it(`${name} - should match snapshot (authenticated)`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`auth-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    allRoutes.forEach(({ path, name }) => {
      it(`${name} - should match snapshot (authenticated)`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`auth-${name}-superadmin`);
      });
    });
  });
});
