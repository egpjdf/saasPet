# ADR-001: Multi-Tenancy Strategy

## Status
Accepted

## Context
Saaspet é uma plataforma SaaS B2B2C para gestão de pet shops, clínicas veterinárias e serviços pet. O modelo de negócio exige isolamento total de dados entre:

1. **Platform Admin** - Super admin da plataforma (acesso global)
2. **Organization** - Franquias/Redes (tenant principal)
3. **Workspace** - Unidades/Filiais (sub-tenant)
4. **User** - Colaboradores (pertencem a 1 Workspace + 1 Organization)

Requisitos não-funcionais:
- Isolamento total de dados (zero vazamento cross-tenant)
- Performance aceitável com milhares de tenants
- Simplicidade operacional (single database)
- Compliance LGPD/GDPR
- Suporte a custom domains futuro

## Decision
**Estratégia: Shared Database + Shared Schema + Row Level Security (PostgreSQL) + Global Scopes (Eloquent)**

### Implementação

#### 1. Database (RLS - Camada 1)
- Habilitar RLS em TODAS tabelas com `organization_id`/`workspace_id`
- Policies: `organization_isolation`, `workspace_isolation`, `platform_admin_bypass`
- Funções helper: `set_tenant_context(org_id, ws_id)`, `set_platform_admin_context()`
- Variáveis de sessão PostgreSQL: `app.current_organization_id`, `app.current_workspace_id`, `app.is_platform_admin`

#### 2. ORM - Global Scopes (Camada 2)
```php
// Traits obrigatórios em Models de dados
trait BelongsToOrganization {
    protected static function booted(): void {
        static::addGlobalScope('organization', function (Builder $builder) {
            $organizationId = app('tenant')->organizationId();
            if ($organizationId) {
                $builder->where('organization_id', $organizationId);
            }
        });
    }
}
```

#### 3. Application - Policies & Middleware (Camada 3)
- `OrganizationPolicy`, `WorkspacePolicy` com Gates
- Middleware pipeline: `SetOrganizationContext` → `SetWorkspaceContext` → `VerifyTenantAccess` → `PreventCrossTenantAccess`

#### 4. Infrastructure (Camada 4)
- Path-based routing: `/admin`, `/{org}/`, `/{org}/{ws}/`
- Tenant resolver com cache Redis (5min TTL)
- Jobs serializam tenant context via `HasTenantContext` trait

### Colunas Obrigatórias
```sql
-- Tabelas de tenant
organization_id UUID NOT NULL REFERENCES organizations(id)

-- Tabelas de workspace
workspace_id UUID NOT NULL REFERENCES workspaces(id)
organization_id UUID NOT NULL REFERENCES organizations(id) -- denormalizado

-- Tabelas de dados operacionais
organization_id UUID NOT NULL REFERENCES organizations(id)
workspace_id UUID NOT NULL REFERENCES workspaces(id)
user_id UUID REFERENCES users(id) -- creator
```

### Índices Compostos Obrigatórios
```sql
CREATE INDEX idx_{table}_org_ws ON {table} (organization_id, workspace_id);
CREATE INDEX idx_{table}_org_status ON {table} (organization_id, status);
CREATE INDEX idx_{table}_ws_created ON {table} (workspace_id, created_at);
```

## Consequences

### Positivos
- ✅ Isolamento forte (RLS no DB = última linha de defesa)
- ✅ Single database = operações simples (backup, migração, monitoramento)
- ✅ Global Scopes = proteção automática no ORM (impossível esquecer WHERE)
- ✅ Performance: índices compostos + RLS nativo do PostgreSQL
- ✅ Compliance: RLS atende requisitos LGPD de isolamento
- ✅ Flexibilidade: Platform Admin bypass via role

### Negativos
- ⚠️ Complexidade inicial de setup (RLS + Global Scopes + Middleware)
- ⚠️ Debugging: queries mostram WHERE extra (expected)
- ⚠️ Raw SQL bypassa Global Scopes (mitigado por RLS)
- ⚠️ Migrations mais verbosas (colunas + índices + FKs)

### Riscos Mitigados
- **Cross-tenant leak:** tenant-guardian valida em CI/CD
- **RLS bypass:** Raw SQL ainda passa por RLS (PostgreSQL enforces)
- **Performance:** Índices compostos + pg_stat_statements monitoring

## Alternatives Considered

| Alternativa | Prós | Contras | Decisão |
|-------------|------|---------|---------|
| **Separate Databases** | Isolamento total | Operational nightmare, costly | ❌ Rejeitado |
| **Separate Schemas** | Isolamento lógico | Cross-schema queries complex, migrations hard | ❌ Rejeitado |
| **Single Schema + Discriminator** | Simple | Easy to forget WHERE, no DB enforcement | ❌ Rejeitado |
| **Row Level Security Only** | DB enforcement | No ORM protection, raw SQL issues | ⚠️ Parcial |
| **Global Scopes Only** | ORM protection | Bypassed by raw SQL, no DB enforcement | ⚠️ Parcial |

## References
- [PostgreSQL Row Level Security](https://www.postgresql.org/docs/current/ddl-rowsecurity.html)
- [Laravel Global Scopes](https://laravel.com/docs/eloquent#global-scopes)
- [Multi-Tenancy in Laravel](https://github.com/stancl/tenancy)

## Compliance
Esta decisão suporta:
- **LGPD Art. 7** - Consentimento por tenant
- **LGPD Art. 18** - Direito de acesso/exportação por tenant
- **LGPD Art. 18 §1º** - Eliminação de dados por tenant
- **GDPR Art. 25** - Data protection by design (RLS = technical measure)