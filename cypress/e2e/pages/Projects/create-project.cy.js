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

    cy.contains('Create Project').click();

    // Fill the Create Project form
    cy.get('input[type="text"]').first().type('My Test Project');
    cy.get('textarea').type('This is a test project created by Cypress.');
    cy.get('input[placeholder="Comma separated"]').type('cypress');

    // Submit the form
    cy.get('button[type="submit"]').contains('Save Project').click();

    // wait 500 ms
    cy.wait(500);

    cy.contains('My Test Project').should('exist');

    // test clean up
    cy.contains('My Test Project').parents('tr').find('button').contains('Delete').click();

  });
});
