# Multi-Tenancy Architecture - Saaspet

> **Referência técnica obrigatória** para todos agents que tocam dados. Regras imutáveis de isolamento.

---

## 🎯 Visão Geral

**Estratégia:** Shared Database + Shared Schema + Row Level Security (PostgreSQL) + Global Scopes (Eloquent)

**Princípio:** **Defense in Depth** - 4 camadas de isolamento que se reforçam:
1. **Database (RLS)** - Última linha de defesa, impossível de bypassar via ORM
2. **ORM (Global Scopes)** - Filtro automático em todas queries Eloquent
3. **Application (Policies/Middleware)** - Autorização explícita por recurso
4. **Infrastructure (Middleware Pipeline)** - Contexto de tenant definido no request

---

## 🏗️ Modelo de Dados

### Hierarquia de Tenant
```
Platform (Super Admin)
    │
    ├── Organization A (Tenant Principal)
    │   │   organization_id: uuid
    │   │
    │   ├── Workspace A1 (Sub-tenant)
    │   │   workspace_id: uuid
    │   │   organization_id: uuid (denormalizado)
    │   │
    │   ├── Workspace A2
    │   │   workspace_id: uuid
    │   │   organization_id: uuid
    │   │
    │   └── Users (pertencem a 1 workspace)
    │       user_id, workspace_id, organization_id
    │
    └── Organization B
        │   organization_id: uuid
        │
        └── Workspace B1
            workspace_id: uuid
            organization_id: uuid
```

### Colunas Obrigatórias em TODAS Tabelas de Dados

```sql
-- Tabelas de tenant (organization, workspace) - apenas organization_id
-- Tabelas de workspace (users, settings) - organization_id + workspace_id
-- Tabelas de dados operacionais - organization_id + workspace_id + user_id (quando aplicável)

ALTER TABLE orders ADD COLUMN organization_id UUID NOT NULL REFERENCES organizations(id);
ALTER TABLE orders ADD COLUMN workspace_id UUID NOT NULL REFERENCES workspaces(id);
ALTER TABLE orders ADD COLUMN user_id UUID REFERENCES users(id); -- creator

-- Índices compostos OBRIGATÓRIOS
CREATE INDEX idx_orders_org_ws ON orders (organization_id, workspace_id);
CREATE INDEX idx_orders_org_status ON orders (organization_id, status);
CREATE INDEX idx_orders_ws_created ON orders (workspace_id, created_at);
```

---

## 🛡️ Camada 1: Row Level Security (PostgreSQL) - **IMUTÁVEL**

### Habilitar RLS
```sql
-- Para TODA tabela com organization_id/workspace_id
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE products ENABLE ROW LEVEL SECURITY;
ALTER TABLE customers ENABLE ROW LEVEL SECURITY;
-- ... todas
```

### Policies Padrão

```sql
-- database/rls_policies.sql

-- 1. Organization Isolation (Dados visíveis apenas da própria org)
CREATE POLICY organization_isolation ON orders
    FOR ALL TO app_user
    USING (organization_id = current_setting('app.current_organization_id')::uuid)
    WITH CHECK (organization_id = current_setting('app.current_organization_id')::uuid);

-- 2. Workspace Isolation (Dados visíveis apenas do próprio workspace)
CREATE POLICY workspace_isolation ON orders
    FOR ALL TO app_user
    USING (workspace_id = current_setting('app.current_workspace_id')::uuid)
    WITH CHECK (workspace_id = current_setting('app.current_workspace_id')::uuid);

-- 3. Platform Admin Bypass (Super admin vê tudo)
CREATE POLICY platform_admin_bypass ON orders
    FOR ALL TO platform_admin
    USING (current_setting('app.is_platform_admin', true)::boolean = true);

-- 4. Cross-Workspace dentro da mesma Org (se permitido por regra de negócio)
-- Exemplo: Organization Admin vê todos workspaces da org
CREATE POLICY org_admin_cross_workspace ON orders
    FOR SELECT TO org_admin
    USING (
        organization_id = current_setting('app.current_organization_id')::uuid
        AND current_setting('app.current_workspace_id', true) IS NULL
    );
```

### Função Helper para Contexto (Chamada pelo Middleware)
```sql
CREATE OR REPLACE FUNCTION set_tenant_context(org_id uuid, ws_id uuid)
RETURNS void LANGUAGE sql AS $$
    SET LOCAL app.current_organization_id = org_id::text;
    SET LOCAL app.current_workspace_id = ws_id::text;
$$;

CREATE OR REPLACE FUNCTION set_platform_admin_context()
RETURNS void LANGUAGE sql AS $$
    SET LOCAL app.is_platform_admin = 'true';
$$;
```

### Variáveis de Sessão (Config no PostgreSQL)
```sql
-- postgresql.conf ou ALTER SYSTEM
custom_variable_classes = 'app'
# Ou via ALTER DATABASE:
ALTER DATABASE saaspet SET custom_variable_classes = 'app';
```

---

## 🛡️ Camada 2: Global Scopes (Eloquent) - **AUTOMÁTICO**

### Traits Obrigatórios

```php
// App\Models\Concerns\BelongsToOrganization.php
trait BelongsToOrganization
{
    protected static function booted(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            $organizationId = app('tenant')->organizationId();
            
            if ($organizationId) {
                $builder->where('organization_id', $organizationId);
            }
        });
    }
    
    // Helper para Platform Admin acessar tudo
    public function scopeWithoutOrganizationScope(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('organization');
    }
}

// App\Models\Concerns\BelongsToWorkspace.php
trait BelongsToWorkspace
{
    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            $workspaceId = app('tenant')->workspaceId();
            
            if ($workspaceId) {
                $builder->where('workspace_id', $workspaceId);
            }
        });
    }
    
    public function scopeWithoutWorkspaceScope(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('workspace');
    }
}
```

### Uso no Model
```php
class Order extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, UsesUuids, HasFactory;
    
    // Global scopes aplicados AUTOMATICAMENTE
    // Order::all() → WHERE organization_id = ? AND workspace_id = ?
}

// Platform Admin: bypass quando necessário
Order::withoutOrganizationScope()->withoutWorkspaceScope()->get();
```

---

## 🛡️ Camada 3: Policies & Gates - **EXPLÍCITO**

### Organization Policy
```php
// App\Policies\OrganizationPolicy.php
class OrganizationPolicy
{
    public function view(User $user, Organization $org): bool
    {
        return $user->organization_id === $org->id;
    }
    
    public function update(User $user, Organization $org): bool
    {
        return $user->organization_id === $org->id 
            && $user->hasRole('admin', $org);
    }
    
    public function delete(User $user, Organization $org): bool
    {
        return $user->organization_id === $org->id 
            && $user->hasRole('owner', $org);
    }
    
    public function manageWorkspaces(User $user, Organization $org): bool
    {
        return $user->organization_id === $org->id 
            && $user->hasRole(['admin', 'owner'], $org);
    }
}
```

### Workspace Policy
```php
// App\Policies\WorkspacePolicy.php
class WorkspacePolicy
{
    public function view(User $user, Workspace $ws): bool
    {
        return $user->workspace_id === $ws->id
            || ($user->organization_id === $ws->organization_id 
                && $user->hasRole('admin', $user->organization));
    }
    
    public function update(User $user, Workspace $ws): bool
    {
        return $user->workspace_id === $ws->id 
            && $user->hasRole('admin', $ws);
    }
    
    public function manageMembers(User $user, Workspace $ws): bool
    {
        return $user->workspace_id === $ws->id 
            && $user->hasRole('admin', $ws);
    }
}
```

### Resource Policy (Genérico para Models de Dados)
```php
// App\Policies\ResourcePolicy.php
class ResourcePolicy
{
    public function view(User $user, Model $resource): bool
    {
        return $this->checkOwnership($user, $resource);
    }
    
    public function update(User $user, Model $resource): bool
    {
        return $this->checkOwnership($user, $resource)
            && $user->hasPermission('update', $resource);
    }
    
    public function delete(User $user, Model $resource): bool
    {
        return $this->checkOwnership($user, $resource)
            && $user->hasPermission('delete', $resource);
    }
    
    protected function checkOwnership(User $user, Model $resource): bool
    {
        // Verifica ambos os níveis
        $orgMatch = $user->organization_id === $resource->organization_id;
        $wsMatch = $user->workspace_id === $resource->workspace_id;
        
        return $orgMatch && $wsMatch;
    }
}
```

### Controller com Attributes (Laravel 13)
```php
#[Middleware('auth')]
#[Middleware('tenant.access')]
class OrderController extends Controller
{
    #[Authorize('view', Order::class)]
    public function show(Order $order) 
    {
        // Policy já validou posse via ResourcePolicy::view
        return OrderResource::make($order);
    }
    
    #[Authorize('update', Order::class)]
    public function update(UpdateOrderRequest $request, Order $order) 
    {
        $order->update($request->validated());
        return OrderResource::make($order);
    }
}
```

---

## 🛡️ Camada 4: Middleware Pipeline - **CONTEXTO**

### 1. SetOrganizationContext
```php
// App\Http\Middleware\SetOrganizationContext.php
class SetOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Path: /admin, /{org}/, /{org}/{ws}/
        $orgSlug = $request->route('organization');
        
        if ($orgSlug) {
            $organization = Organization::where('slug', $orgSlug)->firstOrFail();
            
            // Seta no container (usado por Global Scopes + RLS)
            app('tenant')->setOrganization($organization);
            
            // Seta variável de sessão PG para RLS
            DB::statement("SELECT set_tenant_context(?, ?)", [
                $organization->id, 
                null // workspace será setado depois
            ]);
        }
        
        return $next($request);
    }
}
```

### 2. SetWorkspaceContext
```php
// App\Http\Middleware\SetWorkspaceContext.php
class SetWorkspaceContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $wsSlug = $request->route('workspace');
        
        if ($wsSlug) {
            $organization = app('tenant')->organization();
            
            $workspace = Workspace::where('organization_id', $organization->id)
                ->where('slug', $wsSlug)
                ->firstOrFail();
            
            app('tenant')->setWorkspace($workspace);
            
            // Atualiza RLS context
            DB::statement("SELECT set_tenant_context(?, ?)", [
                $organization->id,
                $workspace->id
            ]);
        }
        
        return $next($request);
    }
}
```

### 3. VerifyTenantAccess
```php
// App\Http\Middleware\VerifyTenantAccess.php
class VerifyTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $organization = app('tenant')->organization();
        $workspace = app('tenant')->workspace();
        
        if (!$user || !$organization) {
            return $next($request); // Platform admin ou rotas públicas
        }
        
        // Verifica se user pertence à organization
        if ($user->organization_id !== $organization->id) {
            abort(403, 'Acesso negado: usuário não pertence a esta organização');
        }
        
        // Se workspace context existe, verifica workspace
        if ($workspace && $user->workspace_id !== $workspace->id) {
            // Org admin pode acessar outros workspaces da mesma org
            if (!$user->hasRole('admin', $organization)) {
                abort(403, 'Acesso negado: usuário não pertence a este workspace');
            }
        }
        
        return $next($request);
    }
}
```

### 4. PreventCrossTenantAccess (Defesa Extra)
```php
// App\Http\Middleware\PreventCrossTenantAccess.php
class PreventCrossTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Headers de segurança
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        return $response;
    }
}
```

### Registro no Kernel
```php
// bootstrap/app.php ou App\Http\Kernel.php
$middleware->group('tenant', [
    \App\Http\Middleware\SetOrganizationContext::class,
    \App\Http\Middleware\SetWorkspaceContext::class,
    \App\Http\Middleware\VerifyTenantAccess::class,
    \App\Http\Middleware\PreventCrossTenantAccess::class,
]);

// Rotas
Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('{organization}')
    ->group(base_path('routes/organization.php'));

Route::middleware(['web', 'auth', 'tenant'])
    ->prefix('{organization}/{workspace}')
    ->group(base_path('routes/workspace.php'));
```

---

## 🔑 Tenant Resolver Service

```php
// App\Services\Tenant\TenantResolver.php
class TenantResolver
{
    public function resolveFromRequest(Request $request): ?Organization
    {
        // 1. Path-based (prioridade)
        if ($orgSlug = $request->route('organization')) {
            return Organization::where('slug', $orgSlug)->first();
        }
        
        // 2. Subdomain fallback (futuro)
        if ($subdomain = $this->extractSubdomain($request)) {
            return Organization::where('subdomain', $subdomain)->first();
        }
        
        // 3. Header (API)
        if ($orgId = $request->header('X-Organization-ID')) {
            return Organization::find($orgId);
        }
        
        // 4. User context (logado)
        if ($user = $request->user()) {
            return $user->organization;
        }
        
        return null;
    }
    
    public function resolveWorkspace(Request $request, Organization $org): ?Workspace
    {
        if ($wsSlug = $request->route('workspace')) {
            return Workspace::where('organization_id', $org->id)
                ->where('slug', $wsSlug)
                ->first();
        }
        
        if ($wsId = $request->header('X-Workspace-ID')) {
            return Workspace::where('organization_id', $org->id)
                ->find($wsId);
        }
        
        if ($user = $request->user()) {
            return $user->workspace;
        }
        
        return null;
    }
}
```

---

## 🎯 Tenant Context Container

```php
// App\Services\Tenant\TenantContext.php
class TenantContext
{
    private ?Organization $organization = null;
    private ?Workspace $workspace = null;
    private bool $isPlatformAdmin = false;
    
    public function setOrganization(Organization $org): void
    {
        $this->organization = $org;
    }
    
    public function setWorkspace(Workspace $ws): void
    {
        $this->workspace = $ws;
    }
    
    public function setPlatformAdmin(bool $value = true): void
    {
        $this->isPlatformAdmin = $value;
    }
    
    public function organizationId(): ?string
    {
        return $this->isPlatformAdmin ? null : $this->organization?->id;
    }
    
    public function workspaceId(): ?string
    {
        return $this->isPlatformAdmin ? null : $this->workspace?->id;
    }
    
    public function organization(): ?Organization
    {
        return $this->organization;
    }
    
    public function workspace(): ?Workspace
    {
        return $this->workspace;
    }
    
    public function isPlatformAdmin(): bool
    {
        return $this->isPlatformAdmin;
    }
    
    // Helper para cache keys
    public function cachePrefix(): string
    {
        if ($this->isPlatformAdmin) return 'platform';
        
        $parts = ['tenant'];
        if ($this->organization) $parts[] = "org:{$this->organization->id}";
        if ($this->workspace) $parts[] = "ws:{$this->workspace->id}";
        
        return implode(':', $parts);
    }
}

// Bind no Service Provider
$this->app->singleton('tenant', TenantContext::class);
```

---

## ✅ Checklist de Validação (Tenant Guardian)

Para **CADA** model/migration/controller/job/event/cache/ai:

| Componente | Verificação |
|------------|-------------|
| **Model** | `use BelongsToOrganization, BelongsToWorkspace` |
| **Migration** | `organization_id` + `workspace_id` + FKs + índices compostos |
| **RLS** | Policy `organization_isolation` + `workspace_isolation` no SQL |
| **Global Scope** | Traits aplicam `where('organization_id', ...)` automaticamente |
| **Policy** | `view/update/delete` verificam `organization_id` E `workspace_id` |
| **Controller** | `#[Authorize]` attribute em métodos |
| **Job** | `implements HasTenantContext` + serializa org_id + ws_id |
| **Cache** | Keys: `tenant:{org_id}:workspace:{ws_id}:...` |
| **Event Listener** | `app('tenant')->setOrganization()/setWorkspace()` no handle() |
| **Vector Search** | `where('organization_id', ...)->where('workspace_id', ...)->whereVectorSimilarTo()` |
| **AI Agent** | `->withContext(['organization_id' => ..., 'workspace_id' => ...])` |

---

## ⚠️ Cenários de Bypass (Bloqueados)

| Tentativa de Bypass | Prevenção |
|---------------------|-----------|
| `Order::withoutGlobalScopes()->get()` | Policy valida posse no controller |
| `DB::table('orders')->get()` | RLS no PostgreSQL bloqueia |
| Manipular `X-Organization-ID` header | Middleware valida contra user autenticado |
| SQL Injection no tenant_id | Prepared statements + RLS |
| Platform Admin acessando sem flag | `is_platform_admin` session var no PG |

---

## 📝 Convenções de Naming

| Elemento | Padrão |
|----------|--------|
| Column (org) | `organization_id` (UUID, FK) |
| Column (ws) | `workspace_id` (UUID, FK) |
| Route param (org) | `{organization}` (slug) |
| Route param (ws) | `{workspace}` (slug) |
| Cache prefix | `tenant:{org_id}:workspace:{ws_id}:` |
| Session var (PG) | `app.current_organization_id`, `app.current_workspace_id` |
| Policy method | `view`, `update`, `delete`, `manage*` |
| Middleware | `SetOrganizationContext`, `SetWorkspaceContext` |

---

**Versão:** 1.0  
**ADR:** ADR-001  
**Owner:** CTO + DB Architect  
**Validação:** Tenant Guardian (automático em todo PR)