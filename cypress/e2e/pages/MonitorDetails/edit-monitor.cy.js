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

    cy.wait(3000);
    cy.contains('Create Project').click();

    // Fill the Create Project form
    cy.get('input[type="text"]').first().type('My Test Project');
    cy.get('textarea').type('This is a test project created by Cypress.');
    cy.get('input[placeholder="Comma separated"]').type('cypress');

    // Submit the form
    cy.get('button[type="submit"]').contains('Save Project').click();

    // wait 500 ms
    cy.wait(500);

    // Click the Details link (not button)
    cy.contains('My Test Project').parents('tr').find('a').contains('Details').click();

    cy.wait(2000);

    // CREATE PING MONITOR
    cy.contains('Create Monitor').click();
    cy.get('input[placeholder="Monitor label"]').type('My Ping Monitor');
    cy.get('select').first().select('Ping Monitor');
    cy.get('input[placeholder="60"]').clear().type('30');
    cy.get('input[placeholder="Badge label"]').type('PingBadge');
    cy.get('select').eq(1).select('Succeeded');
    cy.get('input[placeholder="example.com or 192.168.1.1"]').type('8.8.8.8');
    cy.get('input[placeholder="80"]').type('8080');
    cy.get('button').contains('Create Monitor').click();

    cy.wait(1000);

    cy.contains('My Ping Monitor').parents('tr').find('button').contains('Details').click();

    cy.wait(2000);
    
    // edit monitor
    cy.contains('Edit Monitor').click();
    cy.get('input.monitor-label-input').clear().type('My Edited Ping Monitor');
    cy.get('button[type="submit"]').contains('Save Monitor').click();

    cy.wait(1000);

    // verify label updated
    cy.contains('My Edited Ping Monitor').should('be.visible');

    // go to /projects
    cy.visit('/projects');

    // test clean up
    cy.contains('My Test Project').parents('tr').find('button').contains('Delete').click();

  });
});
