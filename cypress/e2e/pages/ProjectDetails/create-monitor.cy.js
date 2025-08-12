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

    // CREATE WEBSITE MONITOR
    cy.contains('Create Monitor').click();
    cy.get('input[placeholder="Monitor label"]').type('My Website Monitor');
    cy.get('select').first().select('Website Monitor');
    cy.get('input[placeholder="60"]').clear().type('45');
    cy.get('input[placeholder="Badge label"]').type('WebBadge');
    cy.get('select').eq(1).select('Succeeded');
    cy.get('input[placeholder="https://example.com/page"]').type('https://example.com');
    cy.get('input[type="checkbox"]').check();
    cy.get('textarea[placeholder="keyword1, keyword2, keyword3"]').type('home,contact');
    cy.get('button').contains('Create Monitor').click();

    cy.wait(1000);

    // check that page contains project details
    cy.contains('My Test Project');
    cy.contains('Edit Project');
    cy.contains('My Ping Monitor');
    cy.contains('My Website Monitor');

    // go to /projects
    cy.visit('/projects');

    // test clean up
    cy.contains('My Test Project').parents('tr').find('button').contains('Delete').click();

  });
});
