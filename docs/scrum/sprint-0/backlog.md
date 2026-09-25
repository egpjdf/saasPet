# Sprint 0 Backlog - Technical Task Breakdown

## Wave 1: Foundation & Database (Dia 1 - Manhã)

### Task 1.1: ENV-05 - Models + Traits (backend-dev + db-architect) - 3pts
**Files to Create:**
- `app/Models/Organization.php`
- `app/Models/Workspace.php`
- `app/Models/User.php`
- `app/Models/Concerns/BelongsToOrganization.php`
- `app/Models/Concerns/BelongsToWorkspace.php`
- `app/Models/Concerns/HasTenantContext.php`

**Acceptance Criteria:**
- [ ] Organization: UUID PK, slug (unique), name, settings (JSON), status enum
- [ ] Workspace: UUID PK, organization_id FK, slug (unique per org), name, settings, status
- [ ] User: UUID PK, workspace_id FK, organization_id FK (denorm), email, name, role enum, 2FA fields
- [ ] Traits: BelongsToOrganization, BelongsToWorkspace implement Global Scopes
- [ ] HasTenantContext trait for Jobs (serializes org/ws IDs)
- [ ] PHPStan Level 5 clean, strict types, readonly DTOs where applicable

### Task 1.2: ENV-06 - Migrations + RLS (db-architect) - 5pts
**Files to Create:**
- `database/migrations/xxxx_create_organizations_table.php`
- `database/migrations/xxxx_create_workspaces_table.php`
- `database/migrations/xxxx_create_users_table.php`
- `database/rls_policies.sql`
- `database/seeders/TenantSeeder.php`

**Acceptance Criteria:**
- [ ] Migrations with UUID PKs, composite indexes (org_id, ws_id)
- [ ] FKs with cascadeOnDelete for tenant columns
- [ ] RLS policies: organization_isolation, workspace_isolation, platform_admin_bypass
- [ ] Helper functions: set_tenant_context(org_id, ws_id), set_platform_admin_context()
- [ ] Custom variable classes configured for 'app' namespace
- [ ] Seeder creates Platform Admin + demo Org + Workspace + User

### Task 1.3: ENV-04 - GitHub Actions CI (devops-engineer) - 5pts [IN PROGRESS]
**Files to Create/Update:**
- `.github/workflows/ci.yml`

**Acceptance Criteria:**
- [ ] Job: lint (Pint), static-analysis (PHPStan L5), test (Pest 85%), mutation (Infection 70%), dusk
- [ ] Matrix: PHP 8.3, Node 20
- [ ] Services: PostgreSQL 16, Redis 7
- [ ] Cache: composer, npm, pest
- [ ] Artifacts: coverage, infection, dusk screenshots
- [ ] Required checks on PR

## Wave 2: Middleware & Auth (Dia 1 - Tarde)

### Task 2.1: ENV-07 - Middleware Pipeline (backend-dev) - 3pts
**Files to Create:**
- `app/Http/Middleware/SetOrganizationContext.php`
- `app/Http/Middleware/SetWorkspaceContext.php`
- `app/Http/Middleware/VerifyTenantAccess.php`
- `app/Http/Middleware/PreventCrossTenantAccess.php`
- `bootstrap/app.php` (middleware registration)

**Acceptance Criteria:**
- [ ] SetOrganizationContext: resolves org by slug from route, sets app('tenant')->organizationId()
- [ ] SetWorkspaceContext: resolves ws by slug from route, sets app('tenant')->workspaceId()
- [ ] VerifyTenantAccess: validates user belongs to org/ws, 403 if not
- [ ] PreventCrossTenantAccess: blocks manual tenant_id manipulation attempts
- [ ] Order in middleware pipeline correct
- [ ] PHP attributes on controllers: #[Middleware('tenant.access')]

### Task 2.2: ENV-08 - Global Scopes (backend-dev) - 3pts
**Files to Create/Update:**
- `app/Models/Concerns/BelongsToOrganization.php` (complete implementation)
- `app/Models/Concerns/BelongsToWorkspace.php` (complete implementation)
- `app/Services/Tenant/TenantContext.php` (service to hold current tenant)

**Acceptance Criteria:**
- [ ] Global Scope 'organization' filters by app('tenant')->organizationId()
- [ ] Global Scope 'workspace' filters by app('tenant')->workspaceId()
- [ ] Scopes skip when Platform Admin context (is_platform_admin = true)
- [ ] Helper scopes: withoutOrganizationScope(), withoutWorkspaceScope()
- [ ] TenantContext service: organizationId(), workspaceId(), isPlatformAdmin()

### Task 2.3: ENV-11 - Sanctum + Fortify (backend-dev) - 3pts
**Files to Create/Update:**
- `config/sanctum.php`, `config/fortify.php`
- `app/Actions/Fortify/*` (Register, Login, PasswordReset, EmailVerification, TwoFactor)
- `routes/api.php` (Sanctum routes)
- `resources/js/Pages/Auth/*` (Vue pages for login, register, 2FA, reset)

**Acceptance Criteria:**
- [ ] Sanctum SPA config: stateful domains, token abilities
- [ ] Fortify: registration, login, email verification, password reset, 2FA (TOTP)
- [ ] Two-factor authentication with QR code setup
- [ ] Rate limiting on auth endpoints
- [ ] JSON:API compliant responses for API

### Task 2.4: ENV-14 - Pulse + Telescope + Sentry (devops-engineer) - 2pts
**Files to Create/Update:**
- `composer.json` (packages)
- `config/pulse.php`, `config/telescope.php`
- `.env.example` (SENTRY_DSN)
- Docker compose updates for Pulse/Telescope

**Acceptance Criteria:**
- [ ] Laravel Pulse installed (dev only)
- [ ] Laravel Telescope installed (dev only)
- [ ] Sentry SDK configured (staging/prod)
- [ ] Pulse dashboard accessible at /pulse
- [ ] Telescope at /telescope

### Task 2.5: ENV-17 - MinIO Config (devops-engineer) - 2pts
**Files to Create/Update:**
- `config/filesystems.php`
- `docker-compose.yml` (MinIO service)
- `.env.example` (MinIO credentials)

**Acceptance Criteria:**
- [ ] MinIO service in docker-compose
- [ ] Flysystem S3 adapter configured for MinIO
- [ ] Buckets: uploads, backups, imports
- [ ] Presigned URLs working

### Task 2.6: ENV-18 - Dockerfile Multi-stage (devops-engineer) - 3pts
**Files to Create:**
- `Dockerfile` (multi-stage: base, deps, builder, runner)
- `nginx.conf` (optimized for Inertia + Reverb)
- `docker-compose.yml` updates (healthchecks)
- `.dockerignore`

**Acceptance Criteria:**
- [ ] Multi-stage build: composer deps → npm build → production image
- [ ] Nginx: gzip, cache headers, Inertia asset handling, Reverb proxy
- [ ] Healthcheck endpoints: /health, /up
- [ ] Non-root user, minimal attack surface
- [ ] Image size < 500MB

## Wave 3: Policies, Resolver, Frontend & Domain Services (Dia 2)

### Task 3.1: ENV-09 - Policies + Gates (backend-dev) - 2pts
**Files to Create:**
- `app/Policies/OrganizationPolicy.php`
- `app/Policies/WorkspacePolicy.php`
- `app/Providers/AuthServiceProvider.php` (gate registration)

**Acceptance Criteria:**
- [ ] OrganizationPolicy: view, update, delete, manageMembers, manageBilling
- [ ] WorkspacePolicy: view, update, delete, manageMembers, manageSettings
- [ ] Gates: platform-admin, organization-admin, workspace-admin
- [ ] Policy auto-discovery configured
- [ ] Tests: user from Org A cannot access Org B resources

### Task 3.2: ENV-10 - Tenant Resolver (backend-dev) - 3pts
**Files to Create:**
- `app/Services/Tenant/TenantResolver.php`
- `app/Services/Tenant/PathBasedResolver.php`
- `app/Services/Tenant/SubdomainResolver.php`
- Route model binding for Organization, Workspace

**Acceptance Criteria:**
- [ ] Path-based: /{org}/{ws}/ → resolves Organization + Workspace
- [ ] Subdomain fallback: {org}.saaspet.com/{ws}/
- [ ] Platform admin: /admin bypasses tenant resolution
- [ ] Caching: resolved tenants cached (Redis, 5min TTL)
- [ ] Events: TenantResolved fired for audit

### Task 3.3: ENV-16 - Reverb + Echo + Vue Composables (frontend-dev + backend-dev) - 3pts
**Files to Create:**
- `config/reverb.php`, `config/broadcasting.php`
- `resources/js/composables/useReverb.ts`, `usePresence.ts`, `usePrivateChannel.ts`
- `resources/js/plugins/echo.ts`
- `resources/js/stores/websocket.ts` (Pinia)
- Vue components: ConnectionStatus, NotificationBell

**Acceptance Criteria:**
- [ ] Reverb server running (port 8080)
- [ ] Laravel Echo configured with Reverb connector
- [ ] Private channels: `tenant.{org_id}.{ws_id}.user.{user_id}`
- [ ] Presence channels for online users
- [ ] Auto-reconnect, exponential backoff
- [ ] TypeScript types for events

### Task 3.4: ENV-15 - Cashier Stripe (billing-engineer) - 3pts
**Files to Create:**
- `config/cashier.php`
- `app/Models/Subscription.php`, `Plan.php`, `Invoice.php`
- `app/Services/Billing/StripeService.php`
- `app/Http/Controllers/Api/Billing/WebhookController.php`
- Migrations: subscriptions, subscription_items, plans, invoices

**Acceptance Criteria:**
- [ ] Cashier Stripe installed + configured
- [ ] Plan model: Free, Starter, Pro, Enterprise
- [ ] Subscription model with trial support
- [ ] Webhook controller: handles invoice.payment_succeeded, customer.subscription.updated, etc.
- [ ] Idempotency keys on webhooks
- [ ] Tests: subscription create, cancel, resume, trial

### Task 3.5: ENV-22 - Notification Resend (notification-engineer) - 3pts
**Files to Create:**
- `config/notification.php`
- `app/Notifications/Channels/ResendChannel.php`
- `app/Notifications/BaseNotification.php`
- `app/Models/NotificationTemplate.php`, `NotificationPreference.php`
- `app/Services/Notification/TemplateEngine.php`
- Migrations: notification_templates, notification_preferences, notifications

**Acceptance Criteria:**
- [ ] Resend provider integrated
- [ ] Template engine: Blade + variable substitution
- [ ] Preferences: per-user, per-channel (email, in-app, push)
- [ ] Queue: ShouldQueue on notifications
- [ ] Unsubscribe links in emails
- [ ] LGPD: consent tracking per template

### Task 3.6: ENV-23 - Webhook Out + OAuth Base (api-integration-engineer) - 3pts
**Files to Create:**
- `app/Services/Integrations/WebhookDispatcher.php`
- `app/Services/Integrations/OAuthManager.php`
- `app/Models/WebhookEndpoint.php`, `OAuthProvider.php`, `IntegrationLog.php`
- `app/Jobs/DispatchWebhook.php`
- Migrations: webhook_endpoints, oauth_providers, integration_logs

**Acceptance Criteria:**
- [ ] Webhook dispatcher: HMAC SHA256 signature, retry with exponential backoff, DLQ
- [ ] OAuth Manager: Google, Microsoft, GitHub providers base
- [ ] Circuit breaker pattern for external APIs
- [ ] Integration logs: request/response, status, latency
- [ ] Rate limiting per provider

### Task 3.7: ENV-21 - LGPD Compliance (compliance-officer) - 5pts
**Files to Create:**
- `app/Models/Consent.php`, `DataExportRequest.php`, `DeletionRequest.php`
- `app/Services/Compliance/ConsentManager.php`
- `app/Services/Compliance/DataExportService.php`
- `app/Services/Compliance/DeletionService.php`
- `app/Jobs/ProcessDataExport.php`, `ProcessDeletion.php`
- `app/Policies/ConsentPolicy.php`
- Migrations: consents, data_export_requests, deletion_requests, retention_policies

**Acceptance Criteria:**
- [ ] Consent model: granular purposes, versioning, withdrawal
- [ ] Data Export: JSON + CSV, all user data, queued job, signed URL download
- [ ] Right to Deletion: cascades soft deletes, anonymizes references, 30-day grace
- [ ] Retention Policy: configurable per data type, automated cleanup job
- [ ] Audit trail: all consent/export/deletion actions logged
- [ ] DPIA template documented

## Wave 4: CI/CD, Tests, Docs, Security (Dia 3)

### Task 4.1: ENV-12 - Cross-Tenant Tests (qa-engineer + security-auditor) - 5pts
**Files to Create:**
- `tests/Feature/Tenant/CrossTenantIsolationTest.php`
- `tests/Feature/Tenant/RLSPolicyTest.php`
- `tests/Feature/Tenant/GlobalScopeTest.php`
- `tests/Feature/Tenant/PolicyTest.php`
- `tests/Browser/CrossTenantDuskTest.php`

**Acceptance Criteria:**
- [ ] Test: Organization A user cannot see Organization B data (403)
- [ ] Test: Workspace A user cannot see Workspace B data (403)
- [ ] Test: Platform Admin sees all (bypass)
- [ ] Test: Organization Admin sees all workspaces in org
- [ ] Test: RLS policies enforce at DB level (raw SQL)
- [ ] Test: Global scopes apply automatically
- [ ] Test: Policies deny cross-tenant access
- [ ] Mutation testing: Infection ≥70% on tenant code

### Task 4.2: ENV-13 - ADR-001 (docs-writer) - 1pt
**Files to Create:**
- `docs/architecture/adr/001-multi-tenancy-strategy.md`

**Acceptance Criteria:**
- [ ] ADR format: Title, Status, Context, Decision, Consequences
- [ ] Documents: Shared DB + Shared Schema + RLS + Global Scopes
- [ ] Explains 3-level hierarchy
- [ ] Links to multi-tenancy.md

### Task 4.3: ENV-19 - GitHub Actions CD (devops-engineer) - 5pts
**Files to Create:**
- `.github/workflows/cd-staging.yml`
- `.github/workflows/cd-production.yml`
- `.github/environments/staging.yml`, `production.yml`

**Acceptance Criteria:**
- [ ] CD Staging: auto-deploy on merge to main
- [ ] CD Production: manual approval, blue-green or rolling
- [ ] Database migrations run before deploy
- [ ] Health checks post-deploy
- [ ] Rollback on failure
- [ ] Slack/Discord notifications

### Task 4.4: ENV-20 - Secrets Integration (devops-engineer) - 2pts
**Files to Create:**
- `.github/workflows/secrets-sync.yml`
- `scripts/sync-secrets.sh` (1Password CLI)

**Acceptance Criteria:**
- [ ] 1Password CLI configured in CI
- [ ] Secrets injected at runtime (not baked in image)
- [ ] Separate vaults: dev, staging, prod
- [ ] Rotation documented

### Task 4.5: Final Validation (All Agents) - 0pts
- [ ] tenant-guardian: zero tenant leaks
- [ ] security-auditor: 0 critical/high findings
- [ ] qa-engineer: all quality gates pass
- [ ] compliance-officer: LGPD features validated

## Quality Gates Checklist (Per Story)

Before any story moves to Done:
- [ ] Code reviewed by CTO (or designated reviewer)
- [ ] tenant-guardian validation passed
- [ ] security-auditor review passed (if security-relevant)
- [ ] qa-engineer test coverage ≥85% (new code)
- [ ] PHPStan Level 5 clean
- [ ] Laravel Pint formatted
- [ ] Mutation score ≥70% (new code)
- [ ] ADR updated if architectural decision
- [ ] Documentation updated (README, API docs if applicable)