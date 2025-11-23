// Copilot - pending review

describe('Admin Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/admins', name: 'index' },
    { path: '/admins/create', name: 'create' },
    { path: '/admins/2', name: 'show' },
    { path: '/admins/2/edit', name: 'edit' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`admins.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`admins-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`admins.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`admins-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`admins.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`admins-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`admins.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`admins-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`admins.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`admins-${name}-superadmin`);
      });
    });
  });
});
