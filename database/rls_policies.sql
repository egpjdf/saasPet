-- RLS Policies for Saaspet Multi-Tenancy
-- Execute após as migrations: psql -d saaspet -f database/rls_policies.sql

-- 1. Habilitar custom_variable_classes para variáveis de sessão 'app'
ALTER DATABASE saaspet SET custom_variable_classes = 'app';

-- 2. Criar roles de aplicação
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'app_user') THEN
        CREATE ROLE app_user NOINHERIT;
    END IF;
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'platform_admin') THEN
        CREATE ROLE platform_admin NOINHERIT;
    END IF;
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'org_admin') THEN
        CREATE ROLE org_admin NOINHERIT;
    END IF;
END
$$;

-- 3. Funções helper para definir contexto de tenant
CREATE OR REPLACE FUNCTION set_tenant_context(org_id uuid, ws_id uuid)
RETURNS void LANGUAGE sql AS $$
    SET LOCAL app.current_organization_id = org_id::text;
    SET LOCAL app.current_workspace_id = ws_id::text;
$$;

CREATE OR REPLACE FUNCTION set_platform_admin_context()
RETURNS void LANGUAGE sql AS $$
    SET LOCAL app.is_platform_admin = 'true';
$$;

CREATE OR REPLACE FUNCTION clear_tenant_context()
RETURNS void LANGUAGE sql AS $$
    RESET app.current_organization_id;
    RESET app.current_workspace_id;
    RESET app.is_platform_admin;
$$;

-- 4. Habilitar RLS nas tabelas de tenant
ALTER TABLE organizations ENABLE ROW LEVEL SECURITY;
ALTER TABLE workspaces ENABLE ROW LEVEL SECURITY;
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE plans ENABLE ROW LEVEL SECURITY;
ALTER TABLE subscriptions ENABLE ROW LEVEL SECURITY;
ALTER TABLE invoices ENABLE ROW LEVEL SECURITY;

-- 5. Policies para organizations
-- Platform Admin vê tudo
CREATE POLICY platform_admin_organizations ON organizations
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin vê apenas sua organização
CREATE POLICY org_admin_organizations ON organizations
    FOR SELECT TO org_admin
    USING (id = current_setting('app.current_organization_id')::uuid);

-- App user (workspace user) vê apenas sua organização
CREATE POLICY app_user_organizations ON organizations
    FOR SELECT TO app_user
    USING (id = current_setting('app.current_organization_id')::uuid);

-- 6. Policies para workspaces
-- Platform Admin vê tudo
CREATE POLICY platform_admin_workspaces ON workspaces
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin vê todos workspaces da organização
CREATE POLICY org_admin_workspaces ON workspaces
    FOR ALL TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid)
    WITH CHECK (organization_id = current_setting('app.current_organization_id')::uuid);

-- App user vê apenas seu workspace
CREATE POLICY app_user_workspaces ON workspaces
    FOR SELECT TO app_user
    USING (
        id = current_setting('app.current_workspace_id')::uuid
        AND organization_id = current_setting('app.current_organization_id')::uuid
    );

-- 7. Policies para users
-- Platform Admin vê tudo
CREATE POLICY platform_admin_users ON users
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin vê todos users da organização
CREATE POLICY org_admin_users ON users
    FOR ALL TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid)
    WITH CHECK (organization_id = current_setting('app.current_organization_id')::uuid);

-- App user vê apenas users do seu workspace
CREATE POLICY app_user_users ON users
    FOR SELECT TO app_user
    USING (
        workspace_id = current_setting('app.current_workspace_id')::uuid
        AND organization_id = current_setting('app.current_organization_id')::uuid
    );

-- 8. Policies para plans (Billing)
-- Platform Admin vê tudo
CREATE POLICY platform_admin_plans ON plans
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin gerencia plans da organização
CREATE POLICY org_admin_plans ON plans
    FOR ALL TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid)
    WITH CHECK (organization_id = current_setting('app.current_organization_id')::uuid);

-- App user vê plans do seu workspace (se workspace_id preenchido) ou da org
CREATE POLICY app_user_plans ON plans
    FOR SELECT TO app_user
    USING (
        organization_id = current_setting('app.current_organization_id')::uuid
        AND (
            workspace_id IS NULL
            OR workspace_id = current_setting('app.current_workspace_id')::uuid
        )
    );

-- 9. Policies para subscriptions (Billing)
-- Platform Admin vê tudo
CREATE POLICY platform_admin_subscriptions ON subscriptions
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin gerencia subscriptions da organização
CREATE POLICY org_admin_subscriptions ON subscriptions
    FOR ALL TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid)
    WITH CHECK (organization_id = current_setting('app.current_organization_id')::uuid);

-- App user vê apenas subscriptions do seu workspace
CREATE POLICY app_user_subscriptions ON subscriptions
    FOR SELECT TO app_user
    USING (
        organization_id = current_setting('app.current_organization_id')::uuid
        AND workspace_id = current_setting('app.current_workspace_id')::uuid
    );

-- 10. Policies para invoices (Billing)
-- Platform Admin vê tudo
CREATE POLICY platform_admin_invoices ON invoices
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin vê invoices da organização
CREATE POLICY org_admin_invoices ON invoices
    FOR ALL TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid)
    WITH CHECK (organization_id = current_setting('app.current_organization_id')::uuid);

-- App user vê invoices do seu workspace
CREATE POLICY app_user_invoices ON invoices
    FOR SELECT TO app_user
    USING (
        organization_id = current_setting('app.current_organization_id')::uuid
        AND workspace_id = current_setting('app.current_workspace_id')::uuid
    );

-- 11. Policies para webhook_endpoints (Integrations)
-- Platform Admin vê tudo
CREATE POLICY platform_admin_webhook_endpoints ON webhook_endpoints
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin gerencia webhooks da organização
CREATE POLICY org_admin_webhook_endpoints ON webhook_endpoints
    FOR ALL TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid)
    WITH CHECK (organization_id = current_setting('app.current_organization_id')::uuid);

-- App user não tem acesso direto (apenas via API controlada)
CREATE POLICY app_user_webhook_endpoints ON webhook_endpoints
    FOR SELECT TO app_user
    USING (false);

-- 12. Policies para webhook_deliveries (Integrations)
-- Platform Admin vê tudo
CREATE POLICY platform_admin_webhook_deliveries ON webhook_deliveries
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin vê deliveries da organização
CREATE POLICY org_admin_webhook_deliveries ON webhook_deliveries
    FOR SELECT TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid);

-- 13. Policies para oauth_providers (Integrations)
-- Platform Admin vê tudo
CREATE POLICY platform_admin_oauth_providers ON oauth_providers
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin gerencia OAuth da organização
CREATE POLICY org_admin_oauth_providers ON oauth_providers
    FOR ALL TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid)
    WITH CHECK (organization_id = current_setting('app.current_organization_id')::uuid);

-- App user não tem acesso direto
CREATE POLICY app_user_oauth_providers ON oauth_providers
    FOR SELECT TO app_user
    USING (false);

-- 14. Policies para notification_preferences (Notifications)
-- Platform Admin vê tudo
CREATE POLICY platform_admin_notification_preferences ON notification_preferences
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin gerencia preferences da organização
CREATE POLICY org_admin_notification_preferences ON notification_preferences
    FOR ALL TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid)
    WITH CHECK (organization_id = current_setting('app.current_organization_id')::uuid);

-- App user gerencia suas próprias preferences
CREATE POLICY app_user_notification_preferences ON notification_preferences
    FOR ALL TO app_user
    USING (
        user_id = current_setting('app.current_user_id', true)::uuid
        AND organization_id = current_setting('app.current_organization_id')::uuid
        AND workspace_id = current_setting('app.current_workspace_id')::uuid
    )
    WITH CHECK (
        user_id = current_setting('app.current_user_id', true)::uuid
        AND organization_id = current_setting('app.current_organization_id')::uuid
        AND workspace_id = current_setting('app.current_workspace_id')::uuid
    );

-- 15. Policies para notifications (Notifications)
-- Platform Admin vê tudo
CREATE POLICY platform_admin_notifications ON notifications
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin vê notifications da organização
CREATE POLICY org_admin_notifications ON notifications
    FOR SELECT TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid);

-- App user vê apenas suas notifications
CREATE POLICY app_user_notifications ON notifications
    FOR ALL TO app_user
    USING (
        user_id = current_setting('app.current_user_id', true)::uuid
        AND organization_id = current_setting('app.current_organization_id')::uuid
        AND workspace_id = current_setting('app.current_workspace_id')::uuid
    )
    WITH CHECK (
        user_id = current_setting('app.current_user_id', true)::uuid
        AND organization_id = current_setting('app.current_organization_id')::uuid
        AND workspace_id = current_setting('app.current_workspace_id')::uuid
    );

-- 16. Template de policy para tabelas de dados operacionais
-- COPIE ESTE BLOCO PARA CADA TABELA DE NEGÓCIO (orders, products, customers, etc.)
-- SUBSTITUA 'orders' PELO NOME DA TABELA

/*
-- Habilitar RLS
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;

-- Platform Admin bypass
CREATE POLICY platform_admin_orders ON orders
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin acesso cross-workspace dentro da org
CREATE POLICY org_admin_orders ON orders
    FOR ALL TO org_admin
    USING (
        organization_id = current_setting('app.current_organization_id')::uuid
        AND current_setting('app.current_workspace_id', true) IS NULL
    )
    WITH CHECK (
        organization_id = current_setting('app.current_organization_id')::uuid
    );

-- App user isolado por workspace
CREATE POLICY app_user_orders ON orders
    FOR ALL TO app_user
    USING (
        organization_id = current_setting('app.current_organization_id')::uuid
        AND workspace_id = current_setting('app.current_workspace_id')::uuid
    )
    WITH CHECK (
        organization_id = current_setting('app.current_organization_id')::uuid
        AND workspace_id = current_setting('app.current_workspace_id')::uuid
    );
*/

-- 16. Policies para audit_logs (Security)
-- Platform Admin vê tudo
ALTER TABLE audit_logs ENABLE ROW LEVEL SECURITY;

CREATE POLICY platform_admin_audit_logs ON audit_logs
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin vê audit logs da organização
CREATE POLICY org_admin_audit_logs ON audit_logs
    FOR SELECT TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid);

-- App user vê apenas audit logs do seu workspace
CREATE POLICY app_user_audit_logs ON audit_logs
    FOR SELECT TO app_user
    USING (
        workspace_id = current_setting('app.current_workspace_id')::uuid
        AND organization_id = current_setting('app.current_organization_id')::uuid
    );

-- 17. Template de policy para tabelas de dados operacionais
-- COPIE ESTE BLOCO PARA CADA TABELA DE NEGÓCIO (orders, products, customers, etc.)
-- SUBSTITUA 'orders' PELO NOME DA TABELA

/*
-- Habilitar RLS
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;

-- Platform Admin bypass
CREATE POLICY platform_admin_orders ON orders
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin acesso cross-workspace dentro da org
CREATE POLICY org_admin_orders ON orders
    FOR ALL TO org_admin
    USING (
        organization_id = current_setting('app.current_organization_id')::uuid
        AND current_setting('app.current_workspace_id', true) IS NULL
    )
    WITH CHECK (
        organization_id = current_setting('app.current_organization_id')::uuid
    );

-- App user isolado por workspace
CREATE POLICY app_user_orders ON orders
    FOR ALL TO app_user
    USING (
        organization_id = current_setting('app.current_organization_id')::uuid
        AND workspace_id = current_setting('app.current_workspace_id')::uuid
    )
    WITH CHECK (
        organization_id = current_setting('app.current_organization_id')::uuid
        AND workspace_id = current_setting('app.current_workspace_id')::uuid
    );
*/

-- 18. Policies para workspace_users (Multi-tenancy Members)
-- Platform Admin vê tudo
ALTER TABLE workspace_users ENABLE ROW LEVEL SECURITY;

CREATE POLICY platform_admin_workspace_users ON workspace_users
    FOR ALL TO platform_admin
    USING (true)
    WITH CHECK (true);

-- Org Admin vê todos members da organização
CREATE POLICY org_admin_workspace_users ON workspace_users
    FOR ALL TO org_admin
    USING (organization_id = current_setting('app.current_organization_id')::uuid)
    WITH CHECK (organization_id = current_setting('app.current_organization_id')::uuid);

-- Workspace Admin vê members do seu workspace
CREATE POLICY workspace_admin_workspace_users ON workspace_users
    FOR ALL TO org_admin
    USING (
        workspace_id = current_setting('app.current_workspace_id')::uuid
        AND organization_id = current_setting('app.current_organization_id')::uuid
    )
    WITH CHECK (
        workspace_id = current_setting('app.current_workspace_id')::uuid
        AND organization_id = current_setting('app.current_organization_id')::uuid
    );

-- App user vê apenas members do seu workspace
CREATE POLICY app_user_workspace_users ON workspace_users
    FOR SELECT TO app_user
    USING (
        workspace_id = current_setting('app.current_workspace_id')::uuid
        AND organization_id = current_setting('app.current_organization_id')::uuid
    );

-- 13. Índices compostos recomendados para performance com RLS
-- (Já criados nas migrations, mas listados aqui para referência)

/*
CREATE INDEX idx_orders_org_ws ON orders (organization_id, workspace_id);
CREATE INDEX idx_orders_org_status ON orders (organization_id, status);
CREATE INDEX idx_orders_ws_created ON orders (workspace_id, created_at);
CREATE INDEX idx_products_org_ws ON products (organization_id, workspace_id);
CREATE INDEX idx_customers_org_ws ON customers (organization_id, workspace_id);
CREATE INDEX idx_audit_logs_org_created ON audit_logs (organization_id, created_at);
CREATE INDEX idx_audit_logs_ws_created ON audit_logs (workspace_id, created_at);
CREATE INDEX idx_workspace_users_org_ws ON workspace_users (organization_id, workspace_id);
CREATE INDEX idx_workspace_users_user_status ON workspace_users (user_id, status);
*/