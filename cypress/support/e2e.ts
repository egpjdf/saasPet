// Cypress E2E Support File
import './commands';

beforeEach(() => {
  cy.intercept('**/api/**', { fixture: 'api-mock.json' }).as('apiRequests');
});

afterEach(() => {
  cy.clearLocalStorage();
  cy.clearCookies();
});