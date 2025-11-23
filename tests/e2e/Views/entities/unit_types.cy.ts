// Copilot - pending review

describe('Unit Type Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/unit_types', name: 'index' },
    { path: '/unit_types/create', name: 'create' },
    { path: '/unit_types/1', name: 'show' },
    { path: '/unit_types/1/edit', name: 'edit' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`unit_types.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`unit_types-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`unit_types.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`unit_types-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`unit_types.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`unit_types-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`unit_types.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`unit_types-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`unit_types.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`unit_types-${name}-superadmin`);
      });
    });
  });
});
