// Copilot - pending review

describe('Log Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/logs', name: 'index' },
    { path: '/logs/1', name: 'show' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`logs.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`logs-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`logs.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`logs-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`logs.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`logs-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`logs.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`logs-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`logs.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`logs-${name}-superadmin`);
      });
    });
  });
});
