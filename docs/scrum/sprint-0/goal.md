# Sprint 0 Goal

## Sprint Goal
**Ambiente de desenvolvimento 100% funcional com Laravel 13, PostgreSQL+RLS, Redis, Inertia/Vue, CI/CD baseline, e multi-tenancy foundation pronta para features.**

## Sprint Dates
- **Início:** 2024-09-24
- **Fim:** 2024-09-26 (3 dias)
- **Sprint Review:** 2024-09-26

## Sprint Backlog (71 pts total, 18 pts já Done = 53 pts restantes)

| ID | Título | Pts | Status | Agente Principal | Dependências |
|----|--------|-----|--------|------------------|--------------|
| ENV-01 | Docker Compose: PG16+pgvector, Redis7, MinIO, Node20, PHP8.3 | 3 | ✅ Done | devops-engineer | - |
| ENV-02 | Laravel 13 Install + Composer deps | 2 | ✅ Done | backend-dev | ENV-01 |
| ENV-03 | NPM: Vue3+TS+Tailwind+Pinia+Inertia-Vue+Echo+Reverb | 3 | ✅ Done | frontend-dev | ENV-01 |
| ENV-04 | GitHub Actions CI: Pint, PHPStan L5, Pest 85%, Infection 70%, Dusk | 5 | 🟡 In Progress | devops-engineer | ENV-02, ENV-03 |
| ENV-05 | Models: Organization, Workspace, User + Traits | 3 | 🔴 Todo | backend-dev + db-architect | ENV-02 |
| ENV-06 | Migrations + RLS policies SQL | 5 | 🔴 Todo | db-architect | ENV-05 |
| ENV-07 | Middleware: SetOrgContext, SetWsContext, VerifyTenantAccess | 3 | 🔴 Todo | backend-dev | ENV-05 |
| ENV-08 | Global Scopes: BelongsToOrg, BelongsToWs | 3 | 🔴 Todo | backend-dev | ENV-05 |
| ENV-09 | Policies: OrgPolicy, WsPolicy + Gates | 2 | 🔴 Todo | backend-dev | ENV-07 |
| ENV-10 | Tenant Resolver: path-based `{org}/{ws}` + subdomain fallback | 3 | 🔴 Todo | backend-dev | ENV-07 |
| ENV-11 | Sanctum + Fortify: SPA auth, 2FA, reset, verification | 3 | 🔴 Todo | backend-dev | ENV-02 |
| ENV-12 | Testes Cross-Tenant: A ≠ B (403) | 5 | 🔴 Todo | qa-engineer + security-auditor | ENV-06, ENV-08 |
| ENV-13 | ADR-001: Multi-Tenancy Strategy | 1 | 🔴 Todo | docs-writer | ENV-06 |
| ENV-14 | Pulse + Telescope (dev) + Sentry (staging/prod) | 2 | 🔴 Todo | devops-engineer | ENV-01 |
| ENV-15 | Cashier (Stripe) + Webhooks + Subscription Models | 3 | 🔴 Todo | billing-engineer | ENV-02 |
| ENV-16 | Reverb + Echo + Vue Composables | 3 | 🔴 Todo | frontend-dev + backend-dev | ENV-03 |
| ENV-17 | MinIO Config + Flysystem S3 Adapter | 2 | 🔴 Todo | devops-engineer | ENV-01 |
| ENV-18 | Dockerfile multi-stage + nginx.conf + healthchecks | 3 | 🔴 Todo | devops-engineer | ENV-01 |
| ENV-19 | GitHub Actions CD: staging + production | 5 | 🔴 Todo | devops-engineer | ENV-04 |
| ENV-20 | Secrets: 1Password CLI / Vault integration | 2 | 🔴 Todo | devops-engineer | ENV-01 |
| ENV-21 | LGPD: Consent model, data export, deletion job, retention policy | 5 | 🔴 Todo | compliance-officer | ENV-05 |
| ENV-22 | Notification: Resend provider, template engine, preferences | 3 | 🔴 Todo | notification-engineer | ENV-02 |
| ENV-23 | API Integration: Webhook out framework, OAuth providers base | 3 | 🔴 Todo | api-integration-engineer | ENV-02 |

**Total Remaining:** 53 pts
**Capacity (3 dias × team):** ~60 pts (target)

## Definition of Done (Sprint 0)
- [ ] All CI pipelines passing (Pint, PHPStan L5, Pest ≥85%, Infection ≥70%)
- [ ] All cross-tenant isolation tests passing (ENV-12)
- [ ] tenant-guardian validates zero tenant leaks
- [ ] security-auditor approves (0 critical/high findings)
- [ ] qa-engineer validates quality gates
- [ ] Docker compose up works cleanly
- [ ] Laravel serves on localhost with Inertia/Vue
- [ ] Reverb WebSocket connects
- [ ] Documentation (ADR-001) committed