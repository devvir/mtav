// Copilot - pending review

describe('Document Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/documents', name: 'index' },
    { path: '/documents/create', name: 'create' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`documents.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`documents-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`documents.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`documents-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`documents.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`documents-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`documents.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`documents-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`documents.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`documents-${name}-superadmin`);
      });
    });
  });
});
