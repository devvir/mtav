// Copilot - pending review

describe('Audio Routes - Visual Snapshots', () => {
  const routes = [
    { path: '/audios', name: 'index' },
    { path: '/audios/create', name: 'create' },
  ];

  describe('As Guest', () => {
    routes.forEach(({ path, name }) => {
      it(`audios.${name} - should redirect to login`, () => {
        cy.visit(path);
        cy.url().should('include', '/login');
        cy.matchImageSnapshot(`audios-${name}-guest`);
      });
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    routes.forEach(({ path, name }) => {
      it(`audios.${name} - should handle member access`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`audios-${name}-member`);
      });
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    routes.forEach(({ path, name }) => {
      it(`audios.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`audios-${name}-admin1`);
      });
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    routes.forEach(({ path, name }) => {
      it(`audios.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`audios-${name}-admin2`);
      });
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    routes.forEach(({ path, name }) => {
      it(`audios.${name} - should match snapshot`, () => {
        cy.visit(path);
        cy.matchImageSnapshot(`audios-${name}-superadmin`);
      });
    });
  });
});
