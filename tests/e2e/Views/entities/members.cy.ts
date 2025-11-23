// Copilot - pending review

describe('Member Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/members', name: 'index' },
    { path: '/members/create', name: 'create' },
    { path: '/members/1', name: 'show' },
    { path: '/members/1/edit', name: 'edit' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`members.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`members-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`members.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`members-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`members.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`members-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`members.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`members-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`members.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`members-${name}-superadmin`);
      });
    });
  });
});
