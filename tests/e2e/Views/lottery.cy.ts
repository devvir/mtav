// Copilot - pending review

describe('Lottery Route - Visual Snapshots', () => {
  describe('As Guest', () => {
    it('lottery - should redirect to login', () => {
      cy.visit('/lottery');
      cy.url().should('include', '/login');
      cy.matchImageSnapshot('lottery-guest');
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    it('lottery - should match snapshot', () => {
      cy.visit('/lottery');
      cy.matchImageSnapshot('lottery-member');
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    it('lottery - should match snapshot', () => {
      cy.visit('/lottery');
      cy.matchImageSnapshot('lottery-admin1');
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    it('lottery - should match snapshot', () => {
      cy.visit('/lottery');
      cy.matchImageSnapshot('lottery-admin2');
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    it('lottery - should match snapshot', () => {
      cy.visit('/lottery');
      cy.matchImageSnapshot('lottery-superadmin');
    });
  });
});
