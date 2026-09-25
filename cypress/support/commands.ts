// Cypress Support File
import '@testing-library/cypress/add-commands';

// Custom commands for workspace settings
declare global {
  namespace Cypress {
    interface Chainable {
      login(email: string, password: string): Chainable<void>;
      visitWorkspaceSettings(orgSlug: string, wsSlug: string): Chainable<void>;
      selectTab(tab: string): Chainable<void>;
      createIntegration(type: string, config: Record<string, any>): Chainable<void>;
      createWebhook(url: string, events: string[]): Chainable<void>;
    }
  }
}

Cypress.Commands.add('login', (email, password) => {
  cy.session([email, password], () => {
    cy.visit('/login');
    cy.get('input[name="email"]').type(email);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
    cy.url().should('not.include', '/login');
  });
});

Cypress.Commands.add('visitWorkspaceSettings', (orgSlug, wsSlug) => {
  cy.visit(`/${orgSlug}/${wsSlug}/settings`);
  cy.get('[role="tab"]').should('have.length', 5);
});

Cypress.Commands.add('selectTab', (tab) => {
  cy.get(`[role="tab"]`).contains(tab).click();
  cy.get(`[role="tabpanel"]`).should('be.visible');
});

Cypress.Commands.add('createIntegration', (type, config) => {
  cy.get('button').contains(type.charAt(0).toUpperCase() + type.slice(1)).click();
  cy.get('[role="dialog"]').should('be.visible');
  
  Object.entries(config).forEach(([key, value]) => {
    if (typeof value === 'boolean') {
      if (value) cy.get(`input[name="${key}"]`).check();
    } else {
      cy.get(`input[name="${key}"], textarea[name="${key}"]`).type(value);
    }
  });
  
  cy.get('button').contains('Criar Integração').click();
  cy.get('[role="dialog"]').should('not.exist');
});

Cypress.Commands.add('createWebhook', (url, events) => {
  cy.get('button').contains('Novo Webhook').click();
  cy.get('[role="dialog"]').should('be.visible');
  
  cy.get('input[name="url"]').type(url);
  
  events.forEach(event => {
    cy.get(`input[value="${event}"]`).check();
  });
  
  cy.get('button').contains('Criar Webhook').click();
  cy.get('[role="dialog"]').should('not.exist');
});