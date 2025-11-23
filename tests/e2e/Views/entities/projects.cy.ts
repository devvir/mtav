// Copilot - pending review

describe('Project Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/projects', name: 'index' },
    { path: '/projects/create', name: 'create' },
    { path: '/projects/1', name: 'show' },
    { path: '/projects/1/edit', name: 'edit' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`projects.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`projects-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`projects.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`projects-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`projects.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`projects-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`projects.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`projects-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`projects.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`projects-${name}-superadmin`);
      });
    });
  });
});
