// cypress/e2e/projects_api.cy.ts
describe('API Test - GET /monitors without auth', () => {
  it('should return a list of monitors', () => {
    cy.request('/api/v1/monitors')
      .then((response) => {

        // console.log(response.body)


        expect(response.status).to.eq(200);

        expect(response.body).to.be.an('array');

        response.body.forEach(monitor => {
          expect(monitor.label).to.be.a('string').and.not.empty;
        });

        if (response.body.length > 0) {
          expect(response.body[0]).to.have.property('id');
          expect(response.body[0]).to.have.property('label');
        }
      });
  });
});
