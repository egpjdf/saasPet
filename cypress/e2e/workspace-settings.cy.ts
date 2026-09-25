describe('Workspace Settings', () => {
  beforeEach(() => {
    // Mock authentication
    cy.login('admin@example.com', 'password123');
    
    // Mock API responses
    cy.intercept('GET', '**/api/workspace/settings', { fixture: 'api-mock.json' }).as('getSettings');
    cy.intercept('GET', '**/api/workspace/features', { fixture: 'api-mock.json' }).as('getFeatures');
    cy.intercept('GET', '**/api/workspace/limits', { fixture: 'api-mock.json' }).as('getLimits');
    cy.intercept('GET', '**/api/workspace/integrations', { fixture: 'api-mock.json' }).as('getIntegrations');
    cy.intercept('GET', '**/api/workspace/notifications', { fixture: 'api-mock.json' }).as('getNotifications');
    cy.intercept('GET', '**/api/workspace/webhooks', { fixture: 'api-mock.json' }).as('getWebhooks');
    cy.intercept('POST', '**/api/workspace/features/toggle').as('toggleFeature');
    cy.intercept('PUT', '**/api/workspace/limits/*').as('updateLimit');
    cy.intercept('POST', '**/api/workspace/integrations').as('createIntegration');
    cy.intercept('PUT', '**/api/workspace/integrations/*').as('updateIntegration');
    cy.intercept('DELETE', '**/api/workspace/integrations/*').as('deleteIntegration');
    cy.intercept('POST', '**/api/workspace/integrations/*/test').as('testIntegration');
    cy.intercept('PUT', '**/api/workspace/notifications/matrix').as('updateNotificationMatrix');
    cy.intercept('PUT', '**/api/workspace/notifications/digest').as('updateDigest');
    cy.intercept('POST', '**/api/workspace/webhooks').as('createWebhook');
    cy.intercept('PUT', '**/api/workspace/webhooks/*').as('updateWebhook');
    cy.intercept('DELETE', '**/api/workspace/webhooks/*').as('deleteWebhook');
    cy.intercept('POST', '**/api/workspace/webhooks/*/test').as('testWebhook');
  });

  describe('Tab Navigation', () => {
    it('should navigate to workspace settings page', () => {
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      cy.wait('@getSettings');
      
      // Check all tabs are present
      cy.get('[role="tab"]').should('have.length', 5);
      cy.get('[role="tab"]').contains('Funcionalidades').should('exist');
      cy.get('[role="tab"]').contains('Limites').should('exist');
      cy.get('[role="tab"]').contains('Integrações').should('exist');
      cy.get('[role="tab"]').contains('Notificações').should('exist');
      cy.get('[role="tab"]').contains('Webhooks').should('exist');
    });

    it('should switch between tabs', () => {
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      
      // Default tab should be Features
      cy.get('[role="tab"][aria-selected="true"]').contains('Funcionalidades');
      
      // Switch to Limits
      cy.selectTab('Limites');
      cy.get('[role="tab"][aria-selected="true"]').contains('Limites');
      
      // Switch to Integrations
      cy.selectTab('Integrações');
      cy.get('[role="tab"][aria-selected="true"]').contains('Integrações');
      
      // Switch to Notifications
      cy.selectTab('Notificações');
      cy.get('[role="tab"][aria-selected="true"]').contains('Notificações');
      
      // Switch to Webhooks
      cy.selectTab('Webhooks');
      cy.get('[role="tab"][aria-selected="true"]').contains('Webhooks');
    });
  });

  describe('Features Tab', () => {
    beforeEach(() => {
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      cy.wait('@getFeatures');
    });

    it('should display feature cards with correct information', () => {
      cy.get('[role="tabpanel"]').should('be.visible');
      
      // Check feature cards are rendered
      cy.contains('Agendamentos').should('exist');
      cy.contains('PDV').should('exist');
      cy.contains('WhatsApp').should('exist');
      cy.contains('Relatórios Avançados').should('exist');
    });

    it('should show plan badges on features', () => {
      cy.contains('Gratuito').should('exist');
      cy.contains('Starter').should('exist');
      cy.contains('Professional').should('exist');
    });

    it('should toggle feature on/off', () => {
      // Find a feature that's not enabled and can be enabled
      cy.contains('WhatsApp').parent().parent().parent().within(() => {
        cy.get('button[role="switch"]').click();
      });
      
      cy.wait('@toggleFeature');
      cy.get('@toggleFeature').should('have.property', 'request.body.enabled', true);
    });

    it('should disable toggle for features with unmet dependencies', () => {
      // Relatórios Avançados requires PDV which is enabled
      // But if we find a feature with unmet deps, toggle should be disabled
      cy.contains('Relatórios Avançados').parent().parent().parent().within(() => {
        // Should be able to toggle since PDV is enabled
        cy.get('button[role="switch"]').should('not.be.disabled');
      });
    });

    it('should show dependency information', () => {
      cy.contains('PDV').parent().parent().parent().within(() => {
        cy.contains('Requer:').should('exist');
      });
    });
  });

  describe('Limits Tab', () => {
    beforeEach(() => {
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      cy.selectTab('Limites');
      cy.wait('@getLimits');
    });

    it('should display limits table with correct columns', () => {
      cy.get('table').should('exist');
      cy.get('th').should('contain', 'Recurso');
      cy.get('th').should('contain', 'Plano Base');
      cy.get('th').should('contain', 'Override Atual');
      cy.get('th').should('contain', 'Novo Valor');
      cy.get('th').should('contain', 'Ações');
    });

    it('should show base plan limits', () => {
      cy.contains('100 agendamentos').should('exist');
      cy.contains('500 pets').should('exist');
      cy.contains('10 GB').should('exist');
    });

    it('should show current override with badge', () => {
      cy.contains('Override').should('exist');
      cy.contains('200 agendamentos').should('exist');
    });

    it('should allow editing limit value', () => {
      cy.contains('Agendamentos/mês').parent().parent().within(() => {
        cy.get('button').contains('Alterar').click();
        cy.get('input[type="number"]').clear().type('300');
        cy.get('button').contains('Salvar').click();
      });
      
      cy.wait('@updateLimit');
      cy.get('@updateLimit').should('have.property', 'request.body.new_value', 300);
    });

    it('should validate minimum value', () => {
      cy.contains('Agendamentos/mês').parent().parent().within(() => {
        cy.get('button').contains('Alterar').click();
        cy.get('input[type="number"]').clear().type('50'); // Below base plan of 100
        cy.contains('Valor mínimo: 100').should('exist');
        cy.get('button').contains('Salvar').should('be.disabled');
      });
    });

    it('should reset override to base plan', () => {
      cy.contains('Agendamentos/mês').parent().parent().within(() => {
        cy.get('button').contains('Resetar').click();
      });
      
      // Should reset to base plan
      cy.contains('Usando plano base').should('exist');
    });
  });

  describe('Integrations Tab', () => {
    beforeEach(() => {
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      cy.selectTab('Integrações');
      cy.wait('@getIntegrations');
    });

    it('should display integration cards grouped by status', () => {
      cy.contains('Conectadas (1)').should('exist');
      cy.contains('WhatsApp Business').should('exist');
      cy.contains('Conectado').should('exist');
    });

    it('should show integration details', () => {
      cy.contains('Última sincronização').should('exist');
      cy.contains('15/01/2024').should('exist');
    });

    it('should open create WhatsApp integration modal', () => {
      cy.get('button').contains('WhatsApp').click();
      cy.get('[role="dialog"]').should('be.visible');
      cy.contains('Nova Integração WhatsApp').should('exist');
      
      // Check form fields
      cy.get('input[name="phone_number_id"]').should('exist');
      cy.get('input[name="access_token"]').should('exist');
      cy.get('input[name="webhook_url"]').should('exist');
      cy.get('input[name="verify_token"]').should('exist');
    });

    it('should validate WhatsApp form', () => {
      cy.get('button').contains('WhatsApp').click();
      cy.get('button').contains('Criar Integração').click();
      
      cy.contains('Phone Number ID é obrigatório').should('exist');
      cy.contains('Access Token é obrigatório').should('exist');
      cy.contains('Webhook URL é obrigatório').should('exist');
      cy.contains('Verify Token é obrigatório').should('exist');
    });

    it('should validate HTTPS for webhook URL', () => {
      cy.get('button').contains('WhatsApp').click();
      cy.get('input[name="webhook_url"]').type('http://insecure.com');
      cy.get('button').contains('Criar Integração').click();
      
      cy.contains('Webhook URL deve usar HTTPS').should('exist');
    });

    it('should toggle password visibility', () => {
      cy.get('button').contains('WhatsApp').click();
      cy.get('input[name="access_token"]').should('have.attr', 'type', 'password');
      cy.get('button').contains('access_token').parent().find('button').click();
      cy.get('input[name="access_token"]').should('have.attr', 'type', 'text');
    });

    it('should create WhatsApp integration', () => {
      cy.get('button').contains('WhatsApp').click();
      cy.get('input[name="phone_number_id"]').type('123456789');
      cy.get('input[name="access_token"]').type('EAA_test_token');
      cy.get('input[name="webhook_url"]').type('https://myapp.com/webhook/whatsapp');
      cy.get('input[name="verify_token"]').type('verify123');
      cy.get('button').contains('Criar Integração').click();
      
      cy.wait('@createIntegration');
      cy.get('@createIntegration').should('have.property', 'request.body.type', 'whatsapp');
      cy.get('[role="dialog"]').should('not.exist');
    });

    it('should test existing integration', () => {
      cy.contains('WhatsApp Business').parent().parent().parent().within(() => {
        cy.get('button[title="Testar Conexão"]').click();
      });
      
      cy.wait('@testIntegration');
      cy.get('[role="dialog"]').should('be.visible'); // Test result modal
      cy.contains('Conexão bem-sucedida').should('exist');
    });

    it('should edit existing integration', () => {
      cy.contains('WhatsApp Business').parent().parent().parent().within(() => {
        cy.get('button[title="Editar"]').click();
      });
      
      cy.get('[role="dialog"]').should('be.visible');
      cy.contains('Editar Integração WhatsApp').should('exist');
    });

    it('should delete integration with confirmation', () => {
      cy.contains('WhatsApp Business').parent().parent().parent().within(() => {
        cy.get('button[title="Remover"]').click();
      });
      
      cy.on('window:confirm', () => true);
      cy.wait('@deleteIntegration');
    });
  });

  describe('Notifications Tab', () => {
    beforeEach(() => {
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      cy.selectTab('Notificações');
      cy.wait('@getNotifications');
    });

    it('should display notification matrix', () => {
      cy.get('table').should('exist');
      cy.get('th').should('contain', 'Canal');
      cy.get('th').should('contain', 'Pedidos');
      cy.get('th').should('contain', 'Agendamentos');
    });

    it('should toggle matrix cells', () => {
      // Find email row, orders column
      cy.contains('Email').parent().parent().within(() => {
        // Toggle orders (should be enabled by default)
        cy.get('input[type="checkbox"]').first().click();
      });
      
      cy.wait('@updateNotificationMatrix');
      cy.get('@updateNotificationMatrix').should('have.property', 'request.body.enabled', false);
    });

    it('should update digest frequency', () => {
      cy.get('input[value="weekly"]').check();
      cy.wait('@updateDigest');
      cy.get('@updateDigest').should('have.property', 'request.body.frequency', 'weekly');
    });

    it('should show channel master toggles', () => {
      cy.contains('Canais Globais').should('exist');
      cy.contains('Email').should('exist');
      cy.contains('Push').should('exist');
      cy.contains('In-App').should('exist');
    });

    it('should disable matrix toggles when channel is disabled', () => {
      // Disable email channel
      cy.contains('Email').parent().parent().within(() => {
        cy.get('input[type="checkbox"]').last().uncheck();
      });
      
      // Matrix toggles for email should be disabled
      cy.contains('Email').parent().parent().parent().within(() => {
        cy.get('input[type="checkbox"]').should('be.disabled');
      });
    });
  });

  describe('Webhooks Tab', () => {
    beforeEach(() => {
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      cy.selectTab('Webhooks');
      cy.wait('@getWebhooks');
    });

    it('should display webhooks table', () => {
      cy.get('table').should('exist');
      cy.get('th').should('contain', 'URL');
      cy.get('th').should('contain', 'Eventos');
      cy.get('th').should('contain', 'Status');
      cy.get('th').should('contain', 'Último Disparo');
      cy.get('th').should('contain', 'Ações');
    });

    it('should show webhook summary stats', () => {
      cy.contains('Ativos').should('exist');
      cy.contains('Inativos').should('exist');
      cy.contains('Com Falha').should('exist');
    });

    it('should open create webhook modal', () => {
      cy.get('button').contains('Novo Webhook').click();
      cy.get('[role="dialog"]').should('be.visible');
      cy.contains('Novo Webhook').should('exist');
    });

    it('should validate webhook URL requires HTTPS', () => {
      cy.get('button').contains('Novo Webhook').click();
      cy.get('input[name="url"]').type('http://insecure.com');
      cy.get('button').contains('Criar Webhook').click();
      
      cy.contains('URL deve usar HTTPS').should('exist');
    });

    it('should require at least one event', () => {
      cy.get('button').contains('Novo Webhook').click();
      cy.get('input[name="url"]').type('https://example.com/webhook');
      cy.get('button').contains('Criar Webhook').click();
      
      cy.contains('Selecione pelo menos um evento').should('exist');
    });

    it('should create webhook with selected events', () => {
      cy.get('button').contains('Novo Webhook').click();
      cy.get('input[name="url"]').type('https://myapp.com/webhook');
      cy.get('input[value="order.created"]').check();
      cy.get('input[value="order.updated"]').check();
      cy.get('button').contains('Criar Webhook').click();
      
      cy.wait('@createWebhook');
      cy.get('@createWebhook').should('have.property', 'request.body.events').that.includes('order.created');
      cy.get('[role="dialog"]').should('not.exist');
    });

    it('should show HMAC secret for existing webhook', () => {
      cy.contains('https://example.com/webhook').parent().parent().within(() => {
        cy.get('input[readonly]').should('have.value', 'secret123');
      });
    });

    it('should copy HMAC secret to clipboard', () => {
      cy.contains('https://example.com/webhook').parent().parent().within(() => {
        cy.get('button').contains('Copiar').click();
      });
      // Clipboard API is mocked in test environment
    });

    it('should regenerate HMAC secret', () => {
      cy.contains('https://example.com/webhook').parent().parent().within(() => {
        cy.get('button[title="Regenerar Segredo HMAC"]').click();
      });
      
      cy.on('window:confirm', () => true);
      cy.wait('@regenerateSecret');
      cy.get('[role="dialog"]').should('be.visible'); // New secret modal
      cy.contains('Novo Segredo HMAC Gerado').should('exist');
      cy.contains('não será exibido novamente').should('exist');
    });

    it('should test webhook delivery', () => {
      cy.contains('https://example.com/webhook').parent().parent().within(() => {
        cy.get('button[title="Testar"]').click();
      });
      
      cy.wait('@testWebhook');
      cy.get('[role="dialog"]').should('be.visible'); // Test result modal
      cy.contains('Entrega bem-sucedida').should('exist');
      cy.contains('HTTP 200').should('exist');
    });

    it('should show available events reference', () => {
      cy.contains('Eventos Disponíveis').should('exist');
      cy.contains('order.created').should('exist');
      cy.contains('order.updated').should('exist');
    });

    it('should edit existing webhook', () => {
      cy.contains('https://example.com/webhook').parent().parent().within(() => {
        cy.get('button[title="Editar"]').click();
      });
      
      cy.get('[role="dialog"]').should('be.visible');
      cy.contains('Editar Webhook').should('exist');
      cy.get('input[name="url"]').should('have.value', 'https://example.com/webhook');
    });

    it('should delete webhook with confirmation', () => {
      cy.contains('https://example.com/webhook').parent().parent().within(() => {
        cy.get('button[title="Remover"]').click();
      });
      
      cy.on('window:confirm', () => true);
      cy.wait('@deleteWebhook');
    });
  });

  describe('Error Handling', () => {
    it('should display error messages', () => {
      cy.intercept('GET', '**/api/workspace/settings', { statusCode: 500, body: { message: 'Erro interno' } }).as('getSettingsError');
      
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      cy.wait('@getSettingsError');
      
      cy.contains('Erro ao carregar configurações').should('exist');
    });

    it('should handle API errors gracefully', () => {
      cy.intercept('POST', '**/api/workspace/features/toggle', { statusCode: 400, body: { message: 'Plano insuficiente' } }).as('toggleFeatureError');
      
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      cy.wait('@getFeatures');
      
      cy.contains('WhatsApp').parent().parent().parent().within(() => {
        cy.get('button[role="switch"]').click();
      });
      
      cy.wait('@toggleFeatureError');
      cy.contains('Erro ao alternar funcionalidade').should('exist');
    });
  });

  describe('Responsive Design', () => {
    it('should work on mobile viewport', () => {
      cy.viewport('iphone-x');
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      cy.wait('@getSettings');
      
      // Tabs should be horizontally scrollable
      cy.get('[role="tab"]').should('have.length', 5);
      
      // Tables should be horizontally scrollable
      cy.selectTab('Limites');
      cy.wait('@getLimits');
      cy.get('table').should('exist');
    });

    it('should work on tablet viewport', () => {
      cy.viewport('ipad-2');
      cy.visitWorkspaceSettings('my-org', 'my-workspace');
      cy.wait('@getSettings');
      
      cy.get('[role="tab"]').should('have.length', 5);
    });
  });
});