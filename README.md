# Saaspet - Plataforma SaaS Multi-Tenant para Pet Shops

> **Stack:** Laravel 13 + Inertia.js + Vue 3 + TypeScript + Tailwind + PostgreSQL 16 + Redis + Reverb

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- PHP 8.3+ (para desenvolvimento local)
- Node.js 20+ (para desenvolvimento local)

### Com Docker (Recomendado)
```bash
# Subir todos os serviços
docker-compose up -d

# Instalar dependências PHP
docker-compose exec app composer install

# Instalar dependências JS
docker-compose exec app npm install

# Build assets
docker-compose exec app npm run build

# Rodar migrations
docker-compose exec app php artisan migrate

# Seed inicial
docker-compose exec app php artisan db:seed --class=TenantSeeder
```

### Desenvolvimento Local (Sem Docker)
```bash
# Instalar dependências
composer install
npm install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Rodar migrations
php artisan migrate --seed

# Iniciar servidores (terminais separados)
php artisan serve
npm run dev
php artisan reverb:start
php artisan queue:work
php artisan schedule:work
```

## 🏗️ Arquitetura

### Multi-Tenancy (3 Níveis)
```
Platform Admin (super admin) - /admin
    │ owns
    ▼
Organization (tenant) - /{org-slug}/
    │ owns
    ▼
Workspace (sub-tenant) - /{org-slug}/{ws-slug}/
    │ belongs to
    ▼
User (pertence a 1 Workspace + 1 Organization)
```

### Camadas de Isolamento (Defense in Depth)
1. **Database (RLS)** - Row Level Security no PostgreSQL
2. **ORM (Global Scopes)** - Filtro automático em queries Eloquent
3. **Application (Policies/Middleware)** - Autorização explícita
4. **Infrastructure (Middleware Pipeline)** - Contexto de tenant no request

### Stack Tecnológica
| Camada | Tecnologia |
|--------|------------|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Inertia.js + Vue 3 + TypeScript |
| Styling | Tailwind CSS |
| State | Pinia |
| Database | PostgreSQL 16 + pgvector |
| Cache/Queue | Redis 7 (Valkey) |
| Real-time | Laravel Reverb + Echo |
| Email | Laravel Mail → Resend |
| Storage | MinIO (dev) → Cloudflare R2 (prod) |
| Billing | Laravel Cashier (Stripe + Paddle) |
| Auth | Laravel Sanctum + Fortify |
| Testing | Pest + Dusk + Infection |
| Static Analysis | PHPStan Level 5 + Laravel Pint |
| CI/CD | GitHub Actions |
| Observability | Laravel Pulse + Telescope + Sentry |

## 📁 Estrutura do Projeto

```
app/
├── Models/
│   ├── Concerns/           # Traits reutilizáveis (BelongsToOrganization, etc.)
│   ├── Organization.php
│   ├── Workspace.php
│   └── User.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/            # JSON:API Resources
│   │   ├── Platform/       # /admin
│   │   ├── Organization/   # /{org}/
│   │   └── Workspace/      # /{org}/{ws}/
│   ├── Middleware/         # SetOrgContext, SetWsContext, VerifyTenantAccess, PreventCrossTenantAccess
│   ├── Requests/           # Form Requests
│   └── Resources/          # JSON:API / API Resources
├── Jobs/
│   └── Concerns/           # HasTenantContext
├── Services/
│   ├── Tenant/             # TenantContext, TenantResolver
│   └── [Domain]/           # Services por domínio
├── Policies/
├── DTOs/                   # Readonly classes
├── Enums/                  # PHP 8.1+ Enums
└── Providers/
```

## 🧪 Testes

```bash
# Todos os testes
./vendor/bin/pest

# Com coverage
./vendor/bin/pest --coverage

# Mutation testing
./vendor/bin/infection

# Browser tests (Dusk)
./vendor/bin/php artisan dusk

# Lint
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse --level=5
```

## 🔐 Segurança

- **Zero Trust:** Nunca confie no tenant_id do request
- **RLS:** Row Level Security no PostgreSQL (impossível bypassar via ORM)
- **Global Scopes:** Filtro automático em TODAS queries Eloquent
- **Policies:** Autorização explícita por recurso
- **Middleware Pipeline:** Contexto de tenant definido no request
- **Secrets:** 1Password/Vault apenas (`.env` só para development)

## 📦 Deployment

### Staging
- Auto-deploy on merge to `main`
- Health checks post-deploy
- Rollback on failure

### Production
- Manual approval required
- Blue-green deployment
- Database migrations run before deploy

## 📚 Documentação

- [Multi-Tenancy Strategy](docs/architecture/multi-tenancy.md)
- [Coding Standards](docs/architecture/coding-standards.md)
- [Security Requirements](docs/architecture/security-requirements.md)
- [API Contracts](docs/architecture/api-contracts.md)
- [ADR-001: Multi-Tenancy Strategy](docs/architecture/adr/001-multi-tenancy-strategy.md)

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -am 'feat: adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📄 Licença

MIT License - veja [LICENSE](LICENSE) para detalhes.