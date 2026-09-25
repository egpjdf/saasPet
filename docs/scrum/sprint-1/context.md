# Sprint 1 Context - Decisões e Estado

## 📌 Decisões Arquiteturais (ADRs desta Sprint)

| ADR | Título | Status |
|-----|--------|--------|
| ADR-016 | Organization Settings Schema (JSONB vs Columns) | 🔴 Pendente |
| ADR-017 | Workspace Member Roles Enum (admin/member/viewer/custom) | 🟢 **Resolvido** - 3 fixos (admin/member/viewer) via `WorkspaceRole` enum |
| ADR-018 | Impersonation Audit Strategy (Append-only + Immutable) | 🔴 Pendente |
| ADR-019 | Custom Domain Routing (Middleware vs DNS + Wildcard) | 🔴 Pendente |
| ADR-020 | Onboarding Wizard State Management (Server vs Client) | 🔴 Pendente |

---

## 🔧 Sprint 0 Dependencies Status (CRÍTICO)

| ENV | Status | Necessário para | Blocker? |
|-----|--------|----------------|----------|
| ENV-05: Models (Org, Ws, User) | ✅ **Done** | ORG-01, ORG-03, ORG-04 | - |
| ENV-06: Migrations + RLS | ✅ **Done** | ALL | - |
| ENV-07: Middleware Pipeline | ✅ **Done** | ALL | - |
| ENV-08: Global Scopes | ✅ **Done** | ALL | - |
| ENV-09: Policies + Gates | ✅ **Done** | ORG-01, ORG-03, ORG-04, ORG-07 | - |
| ENV-10: Tenant Resolver (path-based) | ✅ **Done** | ALL | - |
| ENV-11: Sanctum + Fortify | ✅ **Done** | ORG-06, ORG-07, ORG-09 | - |
| ENV-12: Cross-Tenant Tests | 🔴 Todo | Quality Gate | **SIM** |

> **Status:** Sprint 0 foundation **COMPLETO**. Wave 1 iniciado.

---

## 🎯 Wave 1 - Progresso (PARALELO)

### ORG-01: Organization CRUD (Platform Admin) - 5 pts ✅ **IMPLEMENTADO**

**Entregues:**
- ✅ Controller: `App\Http\Controllers\Platform\OrganizationController`
- ✅ Requests: `StoreOrganizationRequest`, `UpdateOrganizationRequest`
- ✅ Resources: `OrganizationResource`, `OrganizationCollection` (JSON:API)
- ✅ Routes: `routes/api.php` - `/admin/organizations` com middleware `platform.admin`
- ✅ Tests: `tests/Feature/Platform/OrganizationControllerTest.php` (14 testes)
- ✅ Audit: `organization_created`, `organization_updated`, `organization_deleted`, `organization_restored`
- ✅ Middleware: `RequirePlatformAdmin` criado

### ORG-03: Workspace CRUD (Organization Admin) - 5 pts ✅ **IMPLEMENTADO**

**Entregues:**
- ✅ Controller: `App\Http\Controllers\Organization\WorkspaceController`
- ✅ Requests: `StoreWorkspaceRequest`, `UpdateWorkspaceRequest`
- ✅ Resources: `WorkspaceResource`, `WorkspaceCollection` (JSON:API)
- ✅ Routes: `routes/api.php` - `/{org}/workspaces` com middleware `tenant.auth`
- ✅ Tests: `tests/Feature/Organization/WorkspaceControllerTest.php` (20 testes)

### ORG-04: Workspace Members - 8 pts ✅ **IMPLEMENTADO**

**Entregues:**
- ✅ Enum: `WorkspaceRole` (admin, member, viewer) + `WorkspaceUserStatus` (pending, active, revoked)
- ✅ Migration: `2024_09_24_000021_create_workspace_users_table.php`
- ✅ Model: `WorkspaceUser` com traits + casts
- ✅ Policy: `WorkspaceUserPolicy` (viewAny, invite, updateRole, remove, resendInvite)
- ✅ Service: `WorkspaceMemberService` (invite, accept, updateRole, remove, resendInvite)
- ✅ Controller: `App\Http\Controllers\Organization\WorkspaceMemberController`
- ✅ Requests: `InviteMemberRequest`, `UpdateMemberRoleRequest`
- ✅ Resources: `WorkspaceMemberResource`, `WorkspaceMemberCollection`
- ✅ Job: `SendWorkspaceInviteJob` (implements `HasTenantContext`, `#[Tries(3)]`, `#[Backoff]`, `#[Timeout(60)]`)
- ✅ Notification: `WorkspaceInviteNotification` (email via Resend, signed URL 7 dias)
- ✅ Invite Accept: `WorkspaceInviteController` (signed route)
- ✅ Routes: Nested `workspaces/{workspace}/members` + `invite/accept/{workspace_user}`
- ✅ Tests: `tests/Feature/Organization/WorkspaceMemberControllerTest.php` (16 testes)
- ✅ RLS Policies: `platform_admin_workspace_users`, `org_admin_workspace_users`, `workspace_admin_workspace_users`, `app_user_workspace_users`
- ✅ Audit: `user_invited`, `user_joined`, `user_role_changed`, `user_removed`, `user_invite_resent`

---

## 📋 Definition of Ready (DoR) - Verificação Pré-Wave 1 ✅ **COMPLETO**

- ✅ Sprint 0: ENV-05 a ENV-11 = Done (ENV-12 pendente - cross-tenant tests)
- ✅ Models Organization, Workspace, User criados com traits
- ✅ Migrations + RLS policies aplicadas no PG (via SQL file)
- ✅ Middleware pipeline funcionando (SetOrgContext, SetWsContext, VerifyTenantAccess, PreventCrossTenantAccess)
- ✅ Global Scopes BelongsToOrganization, BelongsToWorkspace ativos
- ✅ Policies OrgPolicy, WsPolicy, WorkspaceUserPolicy, UserPolicy registradas
- ✅ Tenant Resolver path-based `{org}/{ws}` funcionando
- ✅ Sanctum + Fortify configurado (login, register, 2FA, verification)

---

## 🔒 Quality Gates - Próximos Passos

| Story | tenant-guardian | security-auditor | qa-engineer | compliance-officer |
|-------|----------------|------------------|-------------|-------------------|
| ORG-01 | 🔴 Pendente | 🔴 Pendente | 🔴 Pendente | - |
| ORG-03 | 🔴 Pendente | 🔴 Pendente | 🔴 Pendente | - |
| ORG-04 | 🔴 Pendente | 🔴 Pendente | 🔴 Pendente | 🔴 Pendente |

---

## 📝 Decisões Pendentes (Para CO/CTO Alinhar)

1. **Organization Settings Schema:** JSONB flexível vs colunas dedicadas? (ADR-016)
2. **Impersonation Scope:** Platform→Org→Workspace OU Platform→Workspace direto? (ADR-018)
3. **Custom Domains:** Subdomain wildcard (`*.saaspet.com`) + custom domain (CNAME) OU apenas subdomain? (ADR-019)
4. **Onboarding Steps:** Quantos passos? Dados obrigatórios vs opcionais? (ADR-020)

---

## 🚀 Wave 2 - Próximas Tasks (Day 1 Afternoon - Day 2)

| Task | Agent | Dependencies |
|------|-------|--------------|
| ORG-02: Organization Settings API + UI | backend-dev + frontend-dev | ORG-01 ✅ |
| ORG-05: Workspace Settings API + UI | backend-dev + frontend-dev | ORG-03 ✅ |
| ORG-06: User Profile & Preferences API + UI | backend-dev + frontend-dev | ORG-04 ✅ |

---

## 🚀 Wave 3 - Próximas Tasks (Day 2 - Day 3)

| Task | Agent | Dependencies |
|------|-------|--------------|
| ORG-07: Impersonation System | backend-dev + security-auditor | ORG-01, ORG-03, ORG-04 ✅ |
| ORG-08: Domain/Subdomain Routing | devops-engineer + backend-dev | ORG-01, ORG-02 |
| ORG-09: Onboarding Wizard | frontend-dev + backend-dev | ORG-01, ORG-02, ORG-03, ORG-04 ✅ |

---

**Última atualização:** 2024-09-24  
**Próxima revisão:** Daily Standup Day 1  
**Status Wave 1:** 18/18 pts implementados (100%)