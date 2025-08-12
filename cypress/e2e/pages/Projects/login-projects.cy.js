// cypress/e2e/login.cy.ts
describe('Laravel + Inertia Login', () => {
  it('Logs in with valid credentials', () => {
    // Visit home page
    cy.visit('/');

    // Click the login link/button
    cy.contains('Log in').click();

    // Fill in login form
    cy.get('#email').type('test1@example.com');
    cy.get('#password').type('password');


    // Submit the form
    cy.get('button[type="submit"]').click();

    // Assert login worked (adjust URL/text to match your app)
    cy.url().should('include', '/projects');
    cy.contains('Projects').should('be.visible');
  });
});
