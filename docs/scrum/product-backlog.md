# Product Backlog - Saaspet

> **Priorização:** CO (Product Owner) prioriza. CTO estima e valida viabilidade técnica.
> **Formato:** `US-XXX` para User Stories, `TECH-XXX` para Technical Debt/Enablers.

---

## 🎯 Visão do Produto
**Saaspet** - Plataforma SaaS multi-tenant para gestão de pet shops, clínicas veterinárias e serviços pet. Permite que organizações (franquias, redes) gerenciem múltiplos workspaces (unidades/filiais) com isolamento total de dados, billing unificado, IA integrada e ecossistema de integrações.

---

## 📊 Épicos Principais

| Épico | Descrição | Status | Prioridade |
|-------|-----------|--------|------------|
| **EPIC-01** | **Foundation & Multi-Tenancy** - Infra, Auth, Tenant Isolation, RLS | 🟢 Sprint 0 | P0 - Crítico |
| **EPIC-02** | **Organization & Workspace Management** - CRUD, Settings, Members, Roles | 🔴 Backlog | P0 - Crítico |
| **EPIC-03** | **Billing & Subscriptions** - Cashier Stripe/Paddle, Plans, Invoices, Portal | 🔴 Backlog | P0 - Crítico |
| **EPIC-04** | **Platform Admin** - Super admin dashboard, org management, health checks | 🔴 Backlog | P1 - Alto |
| **EPIC-05** | **Core Domain Features** - [Domain específico: pets, appointments, POS, inventory] | 🔴 Backlog | P1 - Alto |
| **EPIC-06** | **AI & Intelligence** - Laravel AI SDK, Embeddings, Vector Search, Agents | 🔴 Backlog | P2 - Médio |
| **EPIC-07** | **Integrations & Webhooks** - OAuth, 3rd party APIs, Webhooks out, CRM sync | 🔴 Backlog | P2 - Médio |
| **EPIC-08** | **Notifications & Communication** - Email, SMS, Push, In-app, Realtime, Digest | 🔴 Backlog | P2 - Médio |
| **EPIC-09** | **Compliance & Security** - LGPD/GDPR, Audit, Consent, Cookie, Breach | 🔴 Backlog | P0 - Crítico |
| **EPIC-10** | **Observability & DevEx** - Pulse, Telescope, Sentry, CI/CD, Docs, Runbooks | 🔴 Backlog | P1 - Alto |

---

## 📋 Backlog Detalhado (Priorizado)

### Sprint 0 - Foundation (EM ANDAMENTO)

| ID | Título | Pts | Épico | Status | Agente | Dependências |
|----|--------|-----|-------|--------|--------|--------------|
| ENV-01 | Docker Compose: PG16+pgvector, Redis7, MinIO, Node20, PHP8.3 | 3 | EPIC-01 | ✅ Done | devops-engineer | - |
| ENV-02 | Laravel 13 Install + Composer deps | 2 | EPIC-01 | ✅ Done | backend-dev | ENV-01 |
| ENV-03 | NPM: Vue3+TS+Tailwind+Pinia+Inertia-Vue+Echo+Reverb | 3 | EPIC-01 | ✅ Done | frontend-dev | ENV-01 |
| ENV-04 | GitHub Actions CI: Pint, PHPStan L5, Pest 85%, Infection 70%, Dusk | 5 | EPIC-01 | 🟡 In Progress | devops-engineer | ENV-02, ENV-03 |
| ENV-05 | Models: Organization, Workspace, User + Traits | 3 | EPIC-01 | 🔴 Todo | backend-dev + db-architect | ENV-02 |
| ENV-06 | Migrations + RLS policies SQL | 5 | EPIC-01 | 🔴 Todo | db-architect | ENV-05 |
| ENV-07 | Middleware: SetOrgContext, SetWsContext, VerifyTenantAccess | 3 | EPIC-01 | 🔴 Todo | backend-dev | ENV-05 |
| ENV-08 | Global Scopes: BelongsToOrg, BelongsToWs | 3 | EPIC-01 | 🔴 Todo | backend-dev | ENV-05 |
| ENV-09 | Policies: OrgPolicy, WsPolicy + Gates | 2 | EPIC-01 | 🔴 Todo | backend-dev | ENV-07 |
| ENV-10 | Tenant Resolver: path-based `{org}/{ws}` + subdomain fallback | 3 | EPIC-01 | 🔴 Todo | backend-dev | ENV-07 |
| ENV-11 | Sanctum + Fortify: SPA auth, 2FA, reset, verification | 3 | EPIC-01 | 🔴 Todo | backend-dev | ENV-02 |
| ENV-12 | Testes Cross-Tenant: A ≠ B (403) | 5 | EPIC-01 | 🔴 Todo | qa-engineer + security-auditor | ENV-06, ENV-08 |
| ENV-13 | ADR-001: Multi-Tenancy Strategy | 1 | EPIC-01 | 🔴 Todo | docs-writer | ENV-06 |
| ENV-14 | Pulse + Telescope (dev) + Sentry (staging/prod) | 2 | EPIC-10 | 🔴 Todo | devops-engineer | ENV-01 |
| ENV-15 | Cashier (Stripe) + Webhooks + Subscription Models | 3 | EPIC-03 | 🔴 Todo | billing-engineer | ENV-02 |
| ENV-16 | Reverb + Echo + Vue Composables | 3 | EPIC-08 | 🔴 Todo | frontend-dev + backend-dev | ENV-03 |
| ENV-17 | MinIO Config + Flysystem S3 Adapter | 2 | EPIC-01 | 🔴 Todo | devops-engineer | ENV-01 |
| ENV-18 | Dockerfile multi-stage + nginx.conf + healthchecks | 3 | EPIC-01 | 🔴 Todo | devops-engineer | ENV-01 |
| ENV-19 | GitHub Actions CD: staging + production | 5 | EPIC-10 | 🔴 Todo | devops-engineer | ENV-04 |
| ENV-20 | Secrets: 1Password CLI / Vault integration | 2 | EPIC-10 | 🔴 Todo | devops-engineer | ENV-01 |
| ENV-21 | LGPD: Consent model, data export, deletion job, retention policy | 5 | EPIC-09 | 🔴 Todo | compliance-officer | ENV-05 |
| ENV-22 | Notification: Resend provider, template engine, preferences | 3 | EPIC-08 | 🔴 Todo | notification-engineer | ENV-02 |
| ENV-23 | API Integration: Webhook out framework, OAuth providers base | 3 | EPIC-07 | 🔴 Todo | api-integration-engineer | ENV-02 |

**Total Sprint 0:** 71 pts

---

### Sprint 1 - Organization & Workspace Management (PLANEJADO)

| ID | Título | Pts | Épico | Prioridade | Agente |
|----|--------|-----|-------|------------|--------|
| ORG-01 | Organization CRUD (Platform Admin) | 5 | EPIC-02 | P0 | backend-dev |
| ORG-02 | Organization Settings (branding, domain, timezone, locale) | 5 | EPIC-02 | P0 | backend-dev + frontend-dev |
| ORG-03 | Workspace CRUD (Organization Admin) | 5 | EPIC-02 | P0 | backend-dev |
| ORG-04 | Workspace Members: Invite, Roles (admin/member/viewer), Remove | 8 | EPIC-02 | P0 | backend-dev + frontend-dev |
| ORG-05 | Workspace Settings (features, limits, integrations) | 5 | EPIC-02 | P1 | backend-dev + frontend-dev |
| ORG-06 | User Profile & Preferences (avatar, locale, timezone, 2FA) | 3 | EPIC-02 | P1 | backend-dev + frontend-dev |
| ORG-07 | Impersonation (Platform Admin → Org Admin → Workspace User) | 5 | EPIC-02 | P1 | backend-dev + security-auditor |
| ORG-08 | Domain/Subdomain routing (custom domains para orgs) | 8 | EPIC-02 | P2 | devops-engineer + backend-dev |
| ORG-09 | Organization Onboarding Wizard (multi-step) | 5 | EPIC-02 | P1 | frontend-dev + backend-dev |

**Total Estimado Sprint 1:** 47 pts

---

### Sprint 2 - Billing & Subscriptions (PLANEJADO)

| ID | Título | Pts | Épico | Prioridade | Agente |
|----|--------|-----|-------|------------|--------|
| BILL-01 | Plans Configuration (Free, Starter, Pro, Enterprise) | 3 | EPIC-03 | P0 | billing-engineer |
| BILL-02 | Stripe Checkout + Portal (Organization level) | 8 | EPIC-03 | P0 | billing-engineer |
| BILL-03 | Paddle Checkout (EU customers) + Tax handling | 8 | EPIC-03 | P0 | billing-engineer |
| BILL-04 | Subscription Lifecycle: Upgrade/Downgrade/Proration | 8 | EPIC-03 | P0 | billing-engineer |
| BILL-05 | Trial Management (14 dias, extensão, conversão) | 5 | EPIC-03 | P0 | billing-engineer |
| BILL-06 | Dunning & Payment Recovery (Stripe automatic + custom) | 5 | EPIC-03 | P1 | billing-engineer |
| BILL-07 | Invoices: Generation, PDF, Email, Download | 5 | EPIC-03 | P0 | billing-engineer + notification-engineer |
| BILL-08 | Usage-based Billing (API calls, AI tokens, Storage) | 8 | EPIC-03 | P1 | billing-engineer |
| BILL-09 | Limits Enforcement (Feature flags por plano) | 5 | EPIC-03 | P0 | billing-engineer + backend-dev |
| BILL-10 | Billing Dashboard (Organization Admin) | 5 | EPIC-03 | P1 | frontend-dev + billing-engineer |

**Total Estimado Sprint 2:** 60 pts

---

### Sprint 3 - Platform Admin (PLANEJADO)

| ID | Título | Pts | Épico | Prioridade | Agente |
|----|--------|-----|-------|------------|--------|
| PLAT-01 | Platform Dashboard: Metrics, Health, Revenue | 8 | EPIC-04 | P0 | backend-dev + frontend-dev |
| PLAT-02 | Organization Management: List, View, Suspend, Impersonate | 5 | EPIC-04 | P0 | backend-dev |
| PLAT-03 | User Management (Platform level): Roles, Audit | 5 | EPIC-04 | P1 | backend-dev |
| PLAT-04 | System Health: Queue, Cache, DB, Reverb, Storage | 5 | EPIC-04 | P1 | devops-engineer + backend-dev |
| PLAT-05 | Feature Flags (Platform-wide) | 3 | EPIC-04 | P2 | backend-dev |
| PLAT-06 | Platform Audit Log (Immutable) | 5 | EPIC-04 | P0 | compliance-officer + backend-dev |

**Total Estimado Sprint 3:** 31 pts

---

### Sprint 4+ - Core Domain (A DEFINIR COM CO)

> **Nota:** Features de domínio específico (pets, appointments, POS, inventory, etc.) serão definidas em colaboração com o CO nas próximas Sprint Plannings.

| Área | Features Potenciais | Prioridade |
|------|---------------------|------------|
| **Pets & Tutors** | CRUD Pet, Tutor, Medical History, Vaccines | P1 |
| **Appointments** | Agendamento, Calendar, Reminders, Waitlist | P1 |
| **POS/Vendas** | Products, Sales, Cart, Payment, Receipt | P1 |
| **Inventory** | Stock, Suppliers, Purchase Orders, Alerts | P2 |
| **Reports/BI** | Dashboards, Exports, Scheduled Reports | P2 |

---

### Technical Debt / Enablers (Contínuo)

| ID | Título | Pts | Prioridade | Agente |
|----|--------|-----|------------|--------|
| TECH-01 | Upgrade Laravel 13.x patches (mensal) | 2/sprint | P1 | backend-dev |
| TECH-02 | Dependency updates (semanal via Dependabot) | 1/sprint | P1 | devops-engineer |
| TECH-03 | Performance optimization (query tuning, indexes) | 3/sprint | P2 | db-architect |
| TECH-04 | Test coverage improvement (alvo 90%) | 2/sprint | P2 | qa-engineer |
| TECH-05 | Documentation debt (ADRs, READMEs, Runbooks) | 2/sprint | P2 | docs-writer |
| TECH-06 | Security hardening (headers, CSP, rate limits) | 2/sprint | P1 | security-auditor |

---

## 📈 Métricas de Backlog

| Métrica | Atual | Target |
|---------|-------|--------|
| **Stories Prontas (DoR)** | 23 | > 2 sprints à frente |
| **Bugs Abertos (P1/P2)** | 0 | 0 |
| **Technical Debt Ratio** | 15% | < 20% |
| **Lead Time (Ideação → Done)** | N/A | < 2 semanas |
| **Deployment Frequency** | N/A | Daily (staging), Weekly (prod) |

---

## 🔄 Processo de Priorização

1. **CO propõe** prioridades baseadas em valor de negócio
2. **CTO valida** viabilidade técnica, dependências, riscos
3. **Team estima** (Planning Poker assíncrono)
4. **Sprint Planning:** CO + CTO + Team alinham Sprint Goal e capacity
5. **Commit:** Team comita com stories do Sprint Backlog

---

**Última atualização:** 2024-09-22  
**Próxima revisão:** Sprint 0 Review  
**Responsável:** CO (priorização) + CTO (validação técnica)