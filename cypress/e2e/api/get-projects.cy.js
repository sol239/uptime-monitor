// cypress/e2e/projects_api.cy.ts
describe('API Test - GET /projects without auth', () => {
  it('should return a list of projects', () => {
    cy.request('/api/v1/projects')
      .then((response) => {

        // console.log(response.body)

        expect(response.status).to.eq(200);

        expect(response.body).to.be.an('array');

        response.body.forEach(project => {
          expect(project.label).to.be.a('string').and.not.empty;
        });

        if (response.body.length > 0) {
          expect(response.body[0]).to.have.property('id');
          expect(response.body[0]).to.have.property('label');
        }
      });
  });
});
