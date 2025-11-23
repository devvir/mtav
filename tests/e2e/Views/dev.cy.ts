// Copilot - pending review

describe('Dev Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/dev', name: 'dashboard' },
    { path: '/dev/cards', name: 'cards' },
    { path: '/dev/entity-cards', name: 'entity-cards' },
    { path: '/dev/filters', name: 'filters' },
    { path: '/dev/flash', name: 'flash' },
    { path: '/dev/flash/all', name: 'flash-all' },
    { path: '/dev/plans', name: 'plans' },
    { path: '/dev/playground', name: 'playground' },
    { path: '/dev/ui', name: 'ui' },
  ];

  // Dev routes are typically superadmin-only, but testing all roles for comprehensive coverage
  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`dev.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`dev-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`dev.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`dev-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`dev.${name} - should handle admin access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`dev-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`dev.${name} - should handle admin access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`dev-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`dev.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`dev-${name}-superadmin`);
      });
    });
  });
});
