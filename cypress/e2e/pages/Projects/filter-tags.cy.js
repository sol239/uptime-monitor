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


        // CREATE PROJECT 1
        cy.contains('Create Project').click();

        cy.get('input[type="text"]').first().type('My Test Project 1');
        cy.get('textarea').type('This is a test project created by Cypress.');
        cy.get('input[placeholder="Comma separated"]').type('cypress');
        cy.get('button[type="submit"]').contains('Save Project').click();
        cy.wait(500);

        // CREATE PROJECT 2
        cy.contains('Create Project').click();

        cy.get('input[type="text"]').first().type('My Test Project 2');
        cy.get('textarea').type('This is a test project created by Cypress.');
        cy.get('input[placeholder="Comma separated"]').type('cyprus');
        cy.get('button[type="submit"]').contains('Save Project').click();
        cy.wait(500);


        // CREATE PROJECT 3
        cy.contains('Create Project').click();

        cy.get('input[type="text"]').first().type('My Test Project 3');
        cy.get('textarea').type('This is a test project created by Cypress.');
        cy.get('input[placeholder="Comma separated"]').type('cyrcus');
        cy.get('button[type="submit"]').contains('Save Project').click();

        cy.wait(1000);

        // FILTER BY TAGS: "cyp"
        cy.get('input[placeholder="Filter by tags"]').type('cyp');

        // Check that Project 1 and 2 are visible, Project 3 is not
        cy.contains('My Test Project 1').should('be.visible');
        cy.contains('My Test Project 2').should('be.visible');
        cy.contains('My Test Project 3').should('not.exist');

        cy.wait(2000);

        cy.get('input[placeholder="Filter by tags"]').clear();



        // test clean up
        cy.contains('My Test Project 1').parents('tr').find('button').contains('Delete').click();
        cy.wait(1000);

        cy.contains('My Test Project 2').parents('tr').find('button').contains('Delete').click();
            cy.wait(1000);

        cy.contains('My Test Project 3').parents('tr').find('button').contains('Delete').click();

    });
});