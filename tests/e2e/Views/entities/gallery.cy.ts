// Copilot - pending review

describe('Gallery Route - Visual Snapshot', () => {
  const routes = [
    { path: '/gallery', name: 'index' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`gallery.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`gallery-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`gallery.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`gallery-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`gallery.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`gallery-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`gallery.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`gallery-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`gallery.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`gallery-${name}-superadmin`);
      });
    });
  });
});
