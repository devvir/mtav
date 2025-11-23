// Copilot - pending review

describe('Dashboard Route - Visual Snapshots', () => {
  describe('As Guest', () => {
    it('dashboard - should redirect to login', () => {
      cy.visit('/');
      cy.url().should('include', '/login');
      cy.matchImageSnapshot('dashboard-guest');
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    it('dashboard - should match snapshot', () => {
      cy.visit('/');
      cy.matchImageSnapshot('dashboard-member');
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    it('dashboard - should match snapshot', () => {
      cy.visit('/');
      cy.matchImageSnapshot('dashboard-admin1');
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    it('dashboard - should match snapshot', () => {
      cy.visit('/');
      cy.matchImageSnapshot('dashboard-admin2');
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    it('dashboard - should match snapshot', () => {
      cy.visit('/');
      cy.matchImageSnapshot('dashboard-superadmin');
    });
  });
});
