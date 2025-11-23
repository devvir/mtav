// Copilot - pending review

describe('Documentation Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/documentation/faq', name: 'faq' },
    { path: '/documentation/guide', name: 'guide' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`documentation.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`documentation-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`documentation.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`documentation-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`documentation.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`documentation-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`documentation.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`documentation-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`documentation.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`documentation-${name}-superadmin`);
      });
    });
  });
});
