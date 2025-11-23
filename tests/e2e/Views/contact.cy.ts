// Copilot - pending review

describe('Contact Route - Visual Snapshots', () => {
  describe('As Guest', () => {
    it('contact - should redirect to login', () => {
      cy.visit('/contact/2');
      cy.url().should('include', '/login');
      cy.matchImageSnapshot('contact-guest');
    });
  });

  describe('As Member', () => {
    beforeEach(() => {
      cy.loginAs('member');
    });

    it('contact - should match snapshot', () => {
      cy.visit('/contact/2');
      cy.matchImageSnapshot('contact-member');
    });
  });

  describe('As Admin (1 project)', () => {
    beforeEach(() => {
      cy.loginAs('admin1');
    });

    it('contact - should match snapshot', () => {
      cy.visit('/contact/2');
      cy.matchImageSnapshot('contact-admin1');
    });
  });

  describe('As Admin (2 projects)', () => {
    beforeEach(() => {
      cy.loginAs('admin2');
    });

    it('contact - should match snapshot', () => {
      cy.visit('/contact/2');
      cy.matchImageSnapshot('contact-admin2');
    });
  });

  describe('As Superadmin', () => {
    beforeEach(() => {
      cy.loginAs('superadmin');
    });

    it('contact - should match snapshot', () => {
      cy.visit('/contact/2');
      cy.matchImageSnapshot('contact-superadmin');
    });
  });
});
