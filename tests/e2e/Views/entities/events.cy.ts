// Copilot - pending review

describe('Event Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/events', name: 'index' },
    { path: '/events/create', name: 'create' },
    { path: '/events/1', name: 'show' },
    { path: '/events/1/edit', name: 'edit' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`events.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`events-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`events.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`events-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`events.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`events-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`events.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`events-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`events.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`events-${name}-superadmin`);
      });
    });
  });
});
