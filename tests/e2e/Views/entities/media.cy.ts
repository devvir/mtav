// Copilot - pending review

describe('Media Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/media', name: 'index' },
    { path: '/media/create', name: 'create' },
    { path: '/media/1', name: 'show' },
    { path: '/media/1/edit', name: 'edit' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`media.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`media-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`media.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`media-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`media.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`media-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`media.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`media-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`media.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`media-${name}-superadmin`);
      });
    });
  });
});
