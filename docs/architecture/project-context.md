# Project Context - Saaspet

> **Documento vivo** - Atualizado a cada sprint pelo CTO. Fonte única de verdade para contexto do projeto.

---

## 🎯 Visão Geral

**Saaspet** é uma plataforma SaaS multi-tenant (B2B2C) para gestão de negócios pet: pet shops, clínicas veterinárias, banho/tosa, hotéis pet, creches.

**Modelo:** Platform Admin → Organization (Franquia/Rede) → Workspace (Unidade/Filial) → User (Colaborador)

**Diferenciais:** Isolamento total de dados (RLS + Global Scopes), Billing unificado por Organization, IA nativa (Laravel AI SDK), Ecossistema de integrações.

---

## 🏗️ Arquitetura de Alto Nível

```
┌─────────────────────────────────────────────────────────────────────┐
                        PLATFORM LAYER
  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
  │   Auth      │  │  Billing    │  │  Admin      │  │  Integrations│
  │ (Sanctum)   │  │ (Cashier)   │  │  Dashboard  │  │  (OAuth/WH)  │
  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
                      TENANT LAYER (Organization)
  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
  │  Settings   │  │  Members    │  │  Workspaces │  │  Billing    │
  │  (Brand)    │  │  (Roles)    │  │  (CRUD)     │  │  (Subs)     │
  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
                      WORKSPACE LAYER
  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
  │   Domain    │  │  Calendar   │  │  POS/       │  │  Reports    │
  │  (Pets,     │  │  (Appts)    │  │  Sales      │  │  (BI)       │
  │   Tutors)   │  │             │  │             │  │             │
  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🛠️ Stack Tecnológica (Decidida)

| Camada | Tecnologia | Versão | Decisão (ADR) |
|--------|------------|--------|---------------|
| **Runtime** | PHP | 8.3+ | ADR-002 |
| **Framework** | Laravel | 13.x | ADR-001 |
| **Frontend** | Inertia.js + Vue 3 + TypeScript | Latest | ADR-003 |
| **Styling** | Tailwind CSS | 3.x | ADR-003 |
| **State** | Pinia | 2.x | ADR-003 |
| **Database** | PostgreSQL | 16 | ADR-004 |
| **Extensions** | pgvector, uuid-ossp, pg_stat_statements | - | ADR-004 |
| **Cache/Queue** | Redis (Valkey) | 7 | ADR-005 |
| **Real-time** | Laravel Reverb + Echo | Latest | ADR-006 |
| **Email** | Laravel Mail → Resend | Latest | ADR-007 |
| **Storage** | MinIO (dev) → Cloudflare R2 (prod) | - | ADR-008 |
| **Billing** | Laravel Cashier (Stripe + Paddle) | Latest | ADR-009 |
| **Auth** | Laravel Sanctum (SPA) + Fortify | Latest | ADR-010 |
| **Testing** | Pest + Dusk + Infection | Latest | ADR-011 |
| **Static Analysis** | PHPStan Level 5 + Laravel Pint | Latest | ADR-011 |
| **CI/CD** | GitHub Actions | - | ADR-012 |
| **Deploy** | Laravel Cloud / Forge + Vapor | TBD | ADR-013 |
| **Observability** | Pulse + Telescope + Sentry | Latest | ADR-014 |
| **AI** | Laravel AI SDK | 13.x | ADR-015 |

---

## 🏢 Multi-Tenancy Strategy (ADR-001)

### Abordagem: **Shared Database + Shared Schema + RLS + Global Scopes**

| Nível | Identificador | Isolamento |
|-------|---------------|------------|
| **Platform** | N/A (super admin) | Acesso global |
| **Organization** | `organization_id` (UUID) | RLS + Global Scope + Middleware |
| **Workspace** | `workspace_id` (UUID) + `organization_id` (denormalizado) | RLS + Global Scope + Middleware |
| **User** | `user_id` + `workspace_id` + `organization_id` | Policies + Gates |

### Roteamento: **Path-Based**
- `/admin` → Platform Admin
- `/{org-slug}/` → Organization Admin
- `/{org-slug}/{ws-slug}/` → Workspace User

### Middleware Pipeline (Ordem Crítica)
1. `SetOrganizationContext` - Resolve org por slug
2. `SetWorkspaceContext` - Resolve workspace por slug
3. `VerifyTenantAccess` - Valida membership
4. `PreventCrossTenantAccess` - Block bypass attempts

---

## 🔐 Segurança - Princípios

1. **Zero Trust:** Nunca confie no tenant_id do request; sempre derive do contexto autenticado
2. **Defense in Depth:** RLS (DB) + Global Scopes (ORM) + Policies (Auth) + Middleware (Request)
3. **Least Privilege:** Users só acessam seu workspace; Org admins só sua org; Platform admins tudo
4. **Audit Everything:** Login, CRUD sensível, billing, permission changes, impersonation
5. **Secrets Management:** 1Password/Vault apenas; `.env` só para development
6. **Webhook Security:** HMAC SHA256 + timestamp + replay protection

---

## 📦 Padrões de Código (Laravel 13)

### PHP Attributes (Obrigatórias)
```php
// Controllers
#[Middleware('auth')]
#[Middleware('tenant.access')]
class OrderController extends Controller
{
    #[Authorize('view', Order::class)]
    public function show(Order $order) { ... }
    
    #[Authorize('update', Order::class)]
    public function update(Request $request, Order $order) { ... }
}

// Jobs
#[Tries(3)]
#[Backoff([10, 60, 300])]
#[Timeout(60)]
class ProcessOrder implements ShouldQueue
{
    use HasTenantContext;
    // ...
}
```

### Models - Traits Obrigatórios
```php
class Order extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, UsesUuids, HasFactory;
    // ...
}
```

### Tipagem Estrita
```php
// SEMPRE
public function getOrders(string $organizationId, string $workspaceId, array $filters = []): Collection
{
    // ...
}

// NUNCA
public function getOrders($orgId, $wsId, $filters = []) { ... }
```

### Service Classes (Single Responsibility)
```php
// App/Services/Domain/OrderService.php
class OrderService
{
    public function __construct(
        private OrderRepository $orders,
        private EventDispatcher $events,
        private LimitEnforcer $limits,
    ) {}
    
    public function create(CreateOrderDTO $dto): Order { ... }
}
```

---

## 🗄️ Database - Padrões

### Migrations
- UUIDs para PKs (`$table->uuid('id')->primary()`)
- FKs com `cascadeOnDelete()` para tenant columns
- Índices compostos: `['organization_id', 'workspace_id']`, `['organization_id', 'created_at']`
- Soft deletes em tabelas de negócio
- RLS policies em arquivo SQL separado (`database/rls_policies.sql`)

### Naming Conventions
- Tables: snake_case plural (`orders`, `order_items`)
- Columns: snake_case (`organization_id`, `created_at`)
- FKs: `{table}_id` (`organization_id`, `workspace_id`)
- Indexes: `idx_{table}_{columns}` (`idx_orders_org_ws_status`)

---

## 🧪 Testing - Estratégia

### Pirâmide de Testes
```
           ┌─────────────┐
           │   Dusk      │  ← 5%  (Critical user journeys)
           │  (Browser)  │
          ┌┴─────────────┴┐
          │  Contract     │  ← 15% (API contracts)
          │  (Pest)       │
         ┌┴────────────────┴┐
         │  Feature/       │  ← 30% (Integration, multi-tenant)
         │  Integration    │
        ┌┴────────────────────┴┐
        │    Unit             │  ← 50% (Services, DTOs, Value Objects)
        │    (Pest)           │
        └─────────────────────┘
```

### Tenant-Aware Test Helpers
```php
// tests/Pest.php
uses()->group('tenant')->each()->beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->workspace = Workspace::factory()->for($this->organization)->create();
    $this->user = User::factory()->for($this->workspace)->create();
});

function actingAsTenant(User $user, Organization $org, Workspace $ws): TestResponse
{
    return actingAs($user)
        ->withHeaders([
            'X-Organization-ID' => $org->id,
            'X-Workspace-ID' => $ws->id,
        ]);
}
```

---

## 🚀 CI/CD - Pipeline

### Branch Strategy
- `main` → Production (tags `v*` trigger deploy)
- `develop` → Staging (auto-deploy on push)
- `feature/*` → PRs para `develop`
- `hotfix/*` → PRs para `main`

### Required Checks (Bloqueiam Merge)
1. Static Analysis (PHPStan L5 + Pint)
2. Unit & Feature Tests (Pest, coverage ≥85%)
3. Mutation Testing (Infection MSI ≥70%)
4. Browser Tests (Dusk)
5. Tenant Isolation Tests
6. Security Scan (TruffleHog + Composer Audit)
7. Docker Build

---

## 📁 Estrutura de Pastas Principais

```
saaspet/
├── app/
│   ├── Models/
│   │   ├── Concerns/           # Traits: BelongsToOrganization, etc.
│   │   ├── Organization.php
│   │   ├── Workspace.php
│   │   └── User.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/            # JSON:API Resources
│   │   │   ├── Platform/       # /admin
│   │   │   ├── Organization/   # /{org}/
│   │   │   └── Workspace/      # /{org}/{ws}/
│   │   ├── Middleware/
│   │   │   ├── SetOrganizationContext.php
│   │   │   ├── SetWorkspaceContext.php
│   │   │   ├── VerifyTenantAccess.php
│   │   │   └── PreventCrossTenantAccess.php
│   │   └── Requests/
│   ├── Policies/
│   ├── Jobs/
│   │   ├── Concerns/HasTenantContext.php
│   │   └── ...
│   ├── Services/
│   │   ├── Tenant/
│   │   ├── Billing/
│   │   ├── AI/
│   │   └── ...
│   └── Providers/
│       └── TenantServiceProvider.php
├── database/
│   ├── migrations/
│   ├── seeders/
│   ├── factories/
│   └── rls_policies.sql
├── resources/
│   ├── js/
│   │   ├── Pages/              # Inertia pages por contexto
│   │   ├── Components/
│   │   ├── Composables/
│   │   └── Layouts/
│   └── views/
├── routes/
│   ├── web.php
│   ├── api.php
│   ├── platform.php
│   ├── organization.php
│   └── workspace.php
├── tests/
│   ├── Feature/
│   │   ├── Platform/
│   │   ├── Organization/
│   │   ├── Workspace/
│   │   └── TenantIsolation/
│   ├── Unit/
│   ├── Browser/
│   ├── Contracts/
│   └── Pest.php
├── config/
│   ├── tenant.php
│   ├── billing.php
│   ├── consent.php
│   └── ...
├── docker/
├── .github/workflows/
└── docs/
    ├── scrum/
    ├── architecture/
    │   └── adr/
    ├── security-audit/
    ├── api/
    └── runbooks/
```

---

## 📋 Decisões Arquiteturais (ADRs)

| ADR | Título | Status | Data |
|-----|--------|--------|------|
| ADR-001 | Multi-Tenancy: Shared DB + RLS + Global Scopes | ✅ Accepted | 2024-09-22 |
| ADR-002 | PHP 8.3+ Only | ✅ Accepted | 2024-09-22 |
| ADR-003 | Inertia + Vue 3 + TS + Tailwind + Pinia | ✅ Accepted | 2024-09-22 |
| ADR-004 | PostgreSQL 16 + pgvector + RLS | ✅ Accepted | 2024-09-22 |
| ADR-005 | Redis 7 (Valkey) para Cache/Queue | ✅ Accepted | 2024-09-22 |
| ADR-006 | Laravel Reverb para Real-time | ✅ Accepted | 2024-09-22 |
| ADR-007 | Resend para Email Transacional | ✅ Accepted | 2024-09-22 |
| ADR-008 | MinIO (dev) → Cloudflare R2 (prod) | ✅ Accepted | 2024-09-22 |
| ADR-009 | Laravel Cashier Stripe + Paddle | ✅ Accepted | 2024-09-22 |
| ADR-010 | Sanctum SPA + Fortify para Auth | ✅ Accepted | 2024-09-22 |
| ADR-011 | Pest + Dusk + Infection + PHPStan L5 + Pint | ✅ Accepted | 2024-09-22 |
| ADR-012 | GitHub Actions para CI/CD | ✅ Accepted | 2024-09-22 |
| ADR-013 | Deploy Target: Laravel Cloud (TBD) | 🟡 Proposed | 2024-09-22 |
| ADR-014 | Pulse + Telescope + Sentry | ✅ Accepted | 2024-09-22 |
| ADR-015 | Laravel AI SDK para Features de IA | ✅ Accepted | 2024-09-22 |

---

## 🎯 Princípios de Design (North Star)

1. **Tenant Isolation First** - Cada linha de código deve considerar isolamento
2. **Security by Default** - Deny all, allow explicit
3. **Type Safety** - PHPStan Level 5 é lei
4. **Testability** - Código difícil de testar = código errado
5. **Observability** - Logs, metrics, traces em tudo
6. **Compliance by Design** - LGPD/GDPR não é afterthought
7. **Developer Experience** - DX = Produtividade = Velocidade
8. **Incremental Delivery** - Ship small, ship often, learn fast

---

**Última atualização:** 2024-09-22 (Sprint 0 Planning)  
**Próxima revisão:** Sprint 1 Planning  
**Owner:** CTO