// Copilot - pending review

describe('Unit Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/units', name: 'index' },
    { path: '/units/create', name: 'create' },
    { path: '/units/1', name: 'show' },
    { path: '/units/1/edit', name: 'edit' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`units.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`units-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`units.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`units-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`units.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`units-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`units.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`units-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`units.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`units-${name}-superadmin`);
      });
    });
  });
});
