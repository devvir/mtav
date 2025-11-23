// Copilot - pending review

describe('Settings Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/settings/profile', name: 'profile' },
    { path: '/settings/password', name: 'password' },
    { path: '/settings/appearance', name: 'appearance' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`settings.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`settings-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`settings.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`settings-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`settings.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`settings-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`settings.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`settings-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`settings.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`settings-${name}-superadmin`);
      });
    });
  });
});
