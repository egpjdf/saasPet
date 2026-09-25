describe('Organization Settings', () => {
  beforeEach(() => {
    cy.intercept('GET', '/api/organization/settings', { fixture: 'organization-settings.json' }).as('getSettings');
    cy.intercept('PUT', '/api/organization/settings/branding', { fixture: 'branding-response.json' }).as('updateBranding');
    cy.intercept('POST', '/api/organization/settings/branding/upload', { fixture: 'upload-response.json' }).as('uploadBranding');
    cy.intercept('PUT', '/api/organization/settings/domain', { fixture: 'domain-response.json' }).as('updateDomain');
    cy.intercept('POST', '/api/organization/settings/domain/validate-cname', { fixture: 'cname-validation.json' }).as('validateCname');
    cy.intercept('GET', '/api/organization/settings/domain/ssl-status*', { fixture: 'ssl-status.json' }).as('checkSsl');
    cy.intercept('PUT', '/api/organization/settings/localization', { fixture: 'localization-response.json' }).as('updateLocalization');
    cy.intercept('GET', '/api/organization/settings/features', { fixture: 'features-response.json' }).as('getFeatures');
    cy.intercept('PUT', '/api/organization/settings/email', { fixture: 'email-response.json' }).as('updateEmail');
    cy.intercept('POST', '/api/organization/settings/email/test', { fixture: 'test-email-response.json' }).as('testEmail');
    cy.intercept('GET', '/api/organization/settings/timezones', { fixture: 'timezones.json' }).as('getTimezones');
    cy.intercept('GET', '/api/organization/settings/locales', { fixture: 'locales.json' }).as('getLocales');
    cy.intercept('GET', '/api/organization/settings/currencies', { fixture: 'currencies.json' }).as('getCurrencies');

    cy.visit('/test-org/settings');
    cy.wait('@getSettings');
  });

  describe('Tab Navigation', () => {
    it('should display all 5 tabs', () => {
      cy.get('[role="tab"]').should('have.length', 5);
      cy.contains('Identidade Visual').should('be.visible');
      cy.contains('Domínio').should('be.visible');
      cy.contains('Localização').should('be.visible');
      cy.contains('Funcionalidades').should('be.visible');
      cy.contains('E-mail').should('be.visible');
    });

    it('should switch between tabs', () => {
      cy.contains('Domínio').click();
      cy.get('[role="tab"][aria-selected="true"]').should('contain', 'Domínio');
      cy.contains('Configuração de Domínio').should('be.visible');

      cy.contains('Localização').click();
      cy.get('[role="tab"][aria-selected="true"]').should('contain', 'Localização');
      cy.contains('Região e Idioma').should('be.visible');

      cy.contains('Funcionalidades').click();
      cy.get('[role="tab"][aria-selected="true"]').should('contain', 'Funcionalidades');
      cy.contains('Funcionalidades da Organização').should('be.visible');

      cy.contains('E-mail').click();
      cy.get('[role="tab"][aria-selected="true"]').should('contain', 'E-mail');
      cy.contains('Configurações de E-mail').should('be.visible');
    });
  });

  describe('Branding Tab', () => {
    beforeEach(() => {
      cy.contains('Identidade Visual').click();
    });

    it('should display logo upload sections', () => {
      cy.contains('Logo (Modo Claro)').should('be.visible');
      cy.contains('Logo (Modo Escuro)').should('be.visible');
      cy.contains('Favicon').should('be.visible');
    });

    it('should display color pickers', () => {
      cy.contains('Cor Primária').should('be.visible');
      cy.contains('Cor Secundária').should('be.visible');
      cy.contains('Cor de Destaque').should('be.visible');
      cy.get('input[type="color"]').should('have.length', 3);
    });

    it('should display custom CSS section', () => {
      cy.contains('CSS Personalizado').should('be.visible');
      cy.get('textarea').should('be.visible');
    });

    it('should show preview buttons', () => {
      cy.contains('Botão Primário').should('be.visible');
      cy.contains('Botão Secundário').should('be.visible');
      cy.contains('Badge Destaque').should('be.visible');
    });
  });

  describe('Domain Tab', () => {
    beforeEach(() => {
      cy.contains('Domínio').click();
    });

    it('should display domain input', () => {
      cy.get('#custom-domain').should('be.visible');
      cy.contains('Usar domínio personalizado').should('be.visible');
    });

    it('should show CNAME verification section', () => {
      cy.contains('Verificação CNAME').should('be.visible');
      cy.contains('Verificar CNAME').should('be.visible');
    });

    it('should show SSL status section', () => {
      cy.contains('Status do SSL/TLS').should('be.visible');
    });

    it('should validate domain format', () => {
      cy.get('#custom-domain').type('invalid-domain');
      cy.contains('Formato de domínio inválido').should('be.visible');
      cy.get('#custom-domain').clear().type('app.exemplo.com');
      cy.contains('Formato de domínio inválido').should('not.exist');
    });
  });

  describe('Localization Tab', () => {
    beforeEach(() => {
      cy.contains('Localização').click();
      cy.wait(['@getTimezones', '@getLocales', '@getCurrencies']);
    });

    it('should display timezone select', () => {
      cy.get('#timezone').should('be.visible');
      cy.get('#timezone option').should('have.length.greaterThan', 1);
    });

    it('should display locale select', () => {
      cy.get('#locale').should('be.visible');
      cy.get('#locale option').should('have.length.greaterThan', 1);
    });

    it('should display currency select', () => {
      cy.get('#currency').should('be.visible');
      cy.get('#currency option').should('have.length.greaterThan', 1);
    });

    it('should display date/time format options', () => {
      cy.get('#date-format').should('be.visible');
      cy.get('#time-format').should('be.visible');
      cy.get('#first-day').should('be.visible');
    });

    it('should show preview values', () => {
      cy.contains('Pré-visualização').should('be.visible');
      cy.contains('Data').should('be.visible');
      cy.contains('Hora').should('be.visible');
      cy.contains('Moeda').should('be.visible');
    });
  });

  describe('Features Tab', () => {
    beforeEach(() => {
      cy.contains('Funcionalidades').click();
      cy.wait('@getFeatures');
    });

    it('should display plan badge', () => {
      cy.contains('Plano:').should('be.visible');
    });

    it('should show feature cards by category', () => {
      cy.contains('Essenciais').should('be.visible');
      cy.contains('Comunicação').should('be.visible');
      cy.contains('Analytics & Relatórios').should('be.visible');
    });

    it('should show feature toggle switches', () => {
      cy.get('[role="switch"]').should('have.length.greaterThan', 0);
    });

    it('should show plan badges on features', () => {
      cy.contains('Gratuito').should('be.visible');
    });
  });

  describe('Email Tab', () => {
    beforeEach(() => {
      cy.contains('E-mail').click();
    });

    it('should display provider options', () => {
      cy.contains('SMTP Personalizado').should('be.visible');
      cy.contains('Resend').should('be.visible');
      cy.contains('SendGrid').should('be.visible');
      cy.contains('Mailgun').should('be.visible');
    });

    it('should show SMTP fields when SMTP selected', () => {
      cy.contains('SMTP Personalizado').click();
      cy.get('#smtp-host').should('be.visible');
      cy.get('#smtp-port').should('be.visible');
      cy.get('#smtp-username').should('be.visible');
      cy.get('#smtp-password').should('be.visible');
      cy.get('#smtp-encryption').should('be.visible');
    });

    it('should show Resend API key when Resend selected', () => {
      cy.contains('Resend').click();
      cy.get('#resend-api-key').should('be.visible');
    });

    it('should show common sender fields', () => {
      cy.get('#from-email').should('be.visible');
      cy.get('#from-name').should('be.visible');
      cy.get('#reply-to').should('be.visible');
    });

    it('should show test email section', () => {
      cy.contains('Teste de E-mail').should('be.visible');
      cy.get('#test-email').should('be.visible');
      cy.contains('Enviar E-mail de Teste').should('be.visible');
    });
  });

  describe('Save Actions', () => {
    it('should save branding settings', () => {
      cy.contains('Identidade Visual').click();
      cy.get('input[type="color"]').first().invoke('val', '#FF0000').trigger('change');
      cy.contains('Salvar Alterações').click();
      cy.wait('@updateBranding');
      cy.contains('Identidade visual salva com sucesso').should('be.visible');
    });

    it('should save domain settings', () => {
      cy.contains('Domínio').click();
      cy.get('#custom-domain').clear().type('app.novodominio.com');
      cy.get('#use-custom-domain').check();
      cy.contains('Salvar Configurações').click();
      cy.wait('@updateDomain');
      cy.contains('Configurações de domínio salvas').should('be.visible');
    });

    it('should save localization settings', () => {
      cy.contains('Localização').click();
      cy.wait(['@getTimezones', '@getLocales', '@getCurrencies']);
      cy.get('#timezone').select('America/New_York');
      cy.contains('Salvar Alterações').click();
      cy.wait('@updateLocalization');
      cy.contains('Configurações de localização salvas').should('be.visible');
    });

    it('should save email settings', () => {
      cy.contains('E-mail').click();
      cy.get('#from-email').clear().type('novo@email.com');
      cy.contains('Salvar Configurações').click();
      cy.wait('@updateEmail');
      cy.contains('Configurações de e-mail salvas').should('be.visible');
    });
  });
});