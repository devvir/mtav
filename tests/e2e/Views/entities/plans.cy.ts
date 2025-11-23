// Copilot - pending review

describe('Plan Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/plans/1', name: 'show' },
    { path: '/plans/1/edit', name: 'edit' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`plans.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`plans-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`plans.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`plans-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`plans.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`plans-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`plans.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`plans-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`plans.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`plans-${name}-superadmin`);
      });
    });
  });
});
