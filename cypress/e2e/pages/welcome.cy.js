describe('Welcome Page', () => {
  it('displays the service description', () => {
    cy.visit('/')

    cy.contains(
      'This monitoring service allows users to create projects and define multiple monitors to regularly'
    ).should('be.visible')
  })
})