// Copilot - pending review

describe('Family Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/families', name: 'index' },
    { path: '/families/create', name: 'create' },
    { path: '/families/1', name: 'show' },
    { path: '/families/1/edit', name: 'edit' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`families.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`families-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`families.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`families-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`families.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`families-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`families.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`families-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`families.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`families-${name}-superadmin`);
      });
    });
  });
});
