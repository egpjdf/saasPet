# API Contracts - Saaspet

> **Especificação OpenAPI 3.1** para APIs públicas e webhooks. Fonte única de verdade para contratos.

---

## 📋 Visão Geral

### Base URLs
| Ambiente | URL |
|----------|-----|
| **Local** | `http://localhost/api` |
| **Staging** | `https://staging.saaspet.com/api` |
| **Production** | `https://saaspet.com/api` |

### Autenticação
| Tipo | Uso | Header |
|------|-----|--------|
| **Sanctum Token** | API cliente (server-to-server) | `Authorization: Bearer {token}` |
| **Session Cookie** | Inertia/SPA (browser) | `Cookie: saaspet_session=...` |
| **Webhook Signature** | Webhooks inbound | `X-Webhook-Signature: sha256=...` |

### Tenant Context (Obrigatório em TODAS requests)
| Header | Descrição | Exemplo |
|--------|-----------|---------|
| `X-Organization-ID` | UUID da Organization | `550e8400-e29b-41d4-a716-446655440000` |
| `X-Workspace-ID` | UUID do Workspace | `550e8400-e29b-41d4-a716-446655440001` |

> **Nota:** Em rotas path-based (`/{org}/{ws}/`), headers são opcionais (middleware resolve via slug). Em `/api/*`, headers **obrigatórios**.

---

## 📡 Convenções Gerais

### Formato de Resposta (JSON:API)
```json
{
  "data": { ... } | [ ... ],
  "meta": { ... },
  "links": { ... },
  "included": [ ... ]
}
```

### Error Response (RFC 9457 - Problem Details)
```json
{
  "type": "https://saaspet.com/errors/validation-error",
  "title": "Validation Failed",
  "status": 422,
  "detail": "The given data was invalid.",
  "instance": "/api/orders",
  "errors": {
    "customer_id": ["The customer id field is required."],
    "items.0.quantity": ["The quantity must be at least 1."]
  }
}
```

### HTTP Status Codes
| Code | Uso |
|------|-----|
| `200` | GET/PUT/PATCH/DELETE sucesso |
| `201` | POST criado (Location header) |
| `204` | DELETE sem conteúdo |
| `400` | Request malformado |
| `401` | Não autenticado |
| `403` | Sem permissão (tenant isolation, policy) |
| `404` | Recurso não encontrado |
| `409` | Conflito (ex: duplicate slug) |
| `422` | Falha de validação |
| `429` | Rate limited |
| `500` | Erro interno |
| `503` | Serviço indisponível (maintenance) |

### Pagination (Padrão Laravel)
```json
{
  "data": [...],
  "links": {
    "first": "https://api.saaspet.com/orders?page=1",
    "last": "https://api.saaspet.com/orders?page=5",
    "prev": null,
    "next": "https://api.saaspet.com/orders?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "https://api.saaspet.com/orders",
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

### Sparse Fieldsets (JSON:API)
```
GET /api/orders?fields[orders]=id,number,status,total
```
```json
{
  "data": [
    { "id": "...", "type": "orders", "attributes": { "id": "...", "number": "ORD-001", "status": "confirmed", "total": 150.00 } }
  ]
}
```

### Includes (Relationships)
```
GET /api/orders?include=customer,items.product
```

---

## 🏷️ Versioning

### URL Versioning
```
/api/v1/orders
/api/v2/orders
```

### Header Versioning (Alternativo)
```
Accept: application/vnd.saaspet.v1+json
```

### Deprecation Policy
- `v1` suportado por 12 meses após `v2` release
- `Sunset` header em responses deprecated
- Changelog em `docs/api/CHANGELOG.md`

---

## 📚 Endpoints Principais

### Authentication
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `POST` | `/auth/login` | Login (email/password) | Public |
| `POST` | `/auth/register` | Register | Public |
| `POST` | `/auth/logout` | Logout | Sanctum |
| `POST` | `/auth/forgot-password` | Password reset request | Public |
| `POST` | `/auth/reset-password` | Password reset confirm | Public |
| `POST` | `/auth/verify-email` | Email verification | Public |
| `POST` | `/auth/2fa/enable` | Enable 2FA | Sanctum |
| `POST` | `/auth/2fa/disable` | Disable 2FA | Sanctum |
| `GET` | `/auth/user` | Current user | Sanctum |

### Organizations (Platform Admin: `/admin`, Org Admin: `/{org}/`)
| Method | Endpoint | Description | Auth | Scope |
|--------|----------|-------------|------|-------|
| `GET` | `/admin/organizations` | List all (platform) | Platform Admin | Global |
| `POST` | `/admin/organizations` | Create organization | Platform Admin | Global |
| `GET` | `/admin/organizations/{org}` | Get organization | Platform Admin | Global |
| `PATCH` | `/admin/organizations/{org}` | Update organization | Platform Admin | Global |
| `DELETE` | `/admin/organizations/{org}` | Delete organization | Platform Admin | Global |
| `GET` | `/{org}/settings` | Get org settings | Org Admin | Organization |
| `PATCH` | `/{org}/settings` | Update org settings | Org Admin | Organization |
| `GET` | `/{org}/members` | List members | Org Admin | Organization |
| `POST` | `/{org}/members` | Invite member | Org Admin | Organization |
| `PATCH` | `/{org}/members/{user}` | Update member role | Org Admin | Organization |
| `DELETE` | `/{org}/members/{user}` | Remove member | Org Admin | Organization |

### Workspaces (`/{org}/{ws}/`)
| Method | Endpoint | Description | Auth | Scope |
|--------|----------|-------------|------|-------|
| `GET` | `/{org}/workspaces` | List workspaces | Org Admin | Organization |
| `POST` | `/{org}/workspaces` | Create workspace | Org Admin | Organization |
| `GET` | `/{org}/workspaces/{ws}` | Get workspace | Workspace Member | Workspace |
| `PATCH` | `/{org}/workspaces/{ws}` | Update workspace | Workspace Admin | Workspace |
| `DELETE` | `/{org}/workspaces/{ws}` | Delete workspace | Workspace Admin | Workspace |
| `GET` | `/{org}/{ws}/members` | List members | Workspace Admin | Workspace |
| `POST` | `/{org}/{ws}/members` | Invite member | Workspace Admin | Workspace |
| `PATCH` | `/{org}/{ws}/members/{user}` | Update role | Workspace Admin | Workspace |
| `DELETE` | `/{org}/{ws}/members/{user}` | Remove member | Workspace Admin | Workspace |

### Users
| Method | Endpoint | Description | Auth | Scope |
|--------|----------|-------------|------|-------|
| `GET` | `/api/user` | Current user profile | Sanctum | Workspace |
| `PATCH` | `/api/user` | Update profile | Sanctum | Workspace |
| `POST` | `/api/user/avatar` | Upload avatar | Sanctum | Workspace |
| `DELETE` | `/api/user/avatar` | Delete avatar | Sanctum | Workspace |
| `GET` | `/api/user/preferences` | Notification preferences | Sanctum | Workspace |
| `PATCH` | `/api/user/preferences` | Update preferences | Sanctum | Workspace |

### Billing (Organization Level)
| Method | Endpoint | Description | Auth | Scope |
|--------|----------|-------------|------|-------|
| `GET` | `/{org}/billing/plans` | List available plans | Org Admin | Organization |
| `GET` | `/{org}/billing/subscription` | Current subscription | Org Admin | Organization |
| `POST` | `/{org}/billing/checkout` | Create checkout session | Org Admin | Organization |
| `GET` | `/{org}/billing/portal` | Customer portal URL | Org Admin | Organization |
| `GET` | `/{org}/billing/invoices` | List invoices | Org Admin | Organization |
| `GET` | `/{org}/billing/invoices/{invoice}` | Get invoice (PDF/JSON) | Org Admin | Organization |
| `POST` | `/{org}/billing/payment-methods` | Add payment method | Org Admin | Organization |
| `DELETE` | `/{org}/billing/payment-methods/{pm}` | Remove payment method | Org Admin | Organization |

### Webhooks (Organization Level)
| Method | Endpoint | Description | Auth | Scope |
|--------|----------|-------------|------|-------|
| `GET` | `/{org}/webhooks` | List endpoints | Org Admin | Organization |
| `POST` | `/{org}/webhooks` | Create endpoint | Org Admin | Organization |
| `GET` | `/{org}/webhooks/{wh}` | Get endpoint | Org Admin | Organization |
| `PATCH` | `/{org}/webhooks/{wh}` | Update endpoint | Org Admin | Organization |
| `DELETE` | `/{org}/webhooks/{wh}` | Delete endpoint | Org Admin | Organization |
| `POST` | `/{org}/webhooks/{wh}/test` | Send test payload | Org Admin | Organization |
| `GET` | `/{org}/webhooks/{wh}/deliveries` | Delivery logs | Org Admin | Organization |

---

## 📦 Schemas (Principais)

### Organization
```json
{
  "type": "object",
  "properties": {
    "id": { "type": "string", "format": "uuid" },
    "name": { "type": "string", "maxLength": 255 },
    "slug": { "type": "string", "pattern": "^[a-z0-9-]+$" },
    "status": { "type": "string", "enum": ["active", "suspended", "cancelled"] },
    "settings": { "type": "object" },
    "trial_ends_at": { "type": "string", "format": "date-time", "nullable": true },
    "created_at": { "type": "string", "format": "date-time" },
    "updated_at": { "type": "string", "format": "date-time" }
  },
  "required": ["id", "name", "slug", "status"]
}
```

### Workspace
```json
{
  "type": "object",
  "properties": {
    "id": { "type": "string", "format": "uuid" },
    "organization_id": { "type": "string", "format": "uuid" },
    "name": { "type": "string", "maxLength": 255 },
    "slug": { "type": "string", "pattern": "^[a-z0-9-]+$" },
    "settings": { "type": "object" },
    "limits": { "type": "object" },
    "created_at": { "type": "string", "format": "date-time" },
    "updated_at": { "type": "string", "format": "date-time" }
  },
  "required": ["id", "organization_id", "name", "slug"]
}
```

### User
```json
{
  "type": "object",
  "properties": {
    "id": { "type": "string", "format": "uuid" },
    "organization_id": { "type": "string", "format": "uuid" },
    "workspace_id": { "type": "string", "format": "uuid" },
    "name": { "type": "string", "maxLength": 255 },
    "email": { "type": "string", "format": "email" },
    "phone": { "type": "string", "maxLength": 20, "nullable": true },
    "locale": { "type": "string", "maxLength": 10 },
    "timezone": { "type": "string", "maxLength": 50 },
    "roles": { "type": "array", "items": { "type": "string" } },
    "two_factor_enabled": { "type": "boolean" },
    "email_verified_at": { "type": "string", "format": "date-time", "nullable": true },
    "created_at": { "type": "string", "format": "date-time" },
    "updated_at": { "type": "string", "format": "date-time" }
  },
  "required": ["id", "organization_id", "workspace_id", "name", "email"]
}
```

### Subscription
```json
{
  "type": "object",
  "properties": {
    "id": { "type": "string", "format": "uuid" },
    "organization_id": { "type": "string", "format": "uuid" },
    "provider": { "type": "string", "enum": ["stripe", "paddle"] },
    "provider_id": { "type": "string" },
    "plan": { "type": "string" },
    "interval": { "type": "string", "enum": ["month", "year"] },
    "status": { "type": "string", "enum": ["trialing", "active", "past_due", "canceled", "incomplete"] },
    "quantity": { "type": "integer" },
    "trial_ends_at": { "type": "string", "format": "date-time", "nullable": true },
    "ends_at": { "type": "string", "format": "date-time", "nullable": true },
    "created_at": { "type": "string", "format": "date-time" },
    "updated_at": { "type": "string", "format": "date-time" }
  }
}
```

### Invoice
```json
{
  "type": "object",
  "properties": {
    "id": { "type": "string", "format": "uuid" },
    "organization_id": { "type": "string", "format": "uuid" },
    "provider": { "type": "string", "enum": ["stripe", "paddle"] },
    "provider_id": { "type": "string" },
    "number": { "type": "string" },
    "status": { "type": "string", "enum": ["draft", "open", "paid", "void", "uncollectible"] },
    "currency": { "type": "string", "enum": ["BRL", "USD", "EUR"] },
    "subtotal": { "type": "number" },
    "tax": { "type": "number" },
    "total": { "type": "number" },
    "due_at": { "type": "string", "format": "date-time", "nullable": true },
    "paid_at": { "type": "string", "format": "date-time", "nullable": true },
    "pdf_url": { "type": "string", "format": "uri", "nullable": true },
    "created_at": { "type": "string", "format": "date-time" }
  }
}
```

### Webhook Endpoint
```json
{
  "type": "object",
  "properties": {
    "id": { "type": "string", "format": "uuid" },
    "organization_id": { "type": "string", "format": "uuid" },
    "workspace_id": { "type": "string", "format": "uuid" },
    "url": { "type": "string", "format": "uri" },
    "events": { "type": "array", "items": { "type": "string" } },
    "active": { "type": "boolean" },
    "retry_count": { "type": "integer" },
    "last_delivery_at": { "type": "string", "format": "date-time", "nullable": true },
    "last_failure_at": { "type": "string", "format": "date-time", "nullable": true },
    "created_at": { "type": "string", "format": "date-time" }
  }
}
```

### Webhook Payload (Outbound)
```json
{
  "event": "order.created",
  "timestamp": 1700000000,
  "payload": {
    "id": "uuid",
    "number": "ORD-001",
    "status": "pending",
    "total": 150.00,
    "customer": { "id": "uuid", "name": "João Silva", "email": "joao@email.com" },
    "items": [
      { "product_id": "uuid", "name": "Ração Premium", "quantity": 2, "unit_price": 75.00 }
    ]
  }
}
```

---

## 🔗 Webhooks Outbound - Especificação

### Headers Enviados
| Header | Descrição |
|--------|-----------|
| `Content-Type` | `application/json` |
| `X-Webhook-Signature` | `sha256={hmac_sha256(payload, secret)}` |
| `X-Webhook-Timestamp` | Unix timestamp (segundos) |
| `X-Webhook-Event` | Nome do evento (ex: `order.created`) |
| `X-Webhook-Delivery` | UUID da entrega (idempotency key) |
| `User-Agent` | `Saaspet-Webhooks/1.0` |

### Verificação (Cliente)
```php
function verifyWebhook(string $payload, string $signature, string $secret): bool
{
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}

function isTimestampValid(string $timestamp, int $tolerance = 300): bool
{
    return abs(time() - (int)$timestamp) <= $tolerance;
}
```

### Retry Policy
| Tentativa | Delay | Total Time |
|-----------|-------|------------|
| 1 | 10s | 10s |
| 2 | 1min | 1m10s |
| 3 | 5min | 6m10s |
| 4 | 30min | 36m10s |
| 5 | 1h | 1h36m |

Após 5 falhas → **Dead Letter Queue** + alerta + endpoint desabilitado.

### Eventos Suportados
```
organization.created, organization.updated, organization.deleted
workspace.created, workspace.updated, workspace.deleted
user.invited, user.joined, user.removed, user.role_changed
subscription.created, subscription.updated, subscription.canceled
invoice.created, invoice.paid, invoice.failed, invoice.refunded
order.created, order.updated, order.completed, order.cancelled
payment_method.added, payment_method.removed
*.created, *.updated, *.deleted (wildcards)
```

---

## 🌐 OAuth 2.0 / OIDC (Social Login + API Access)

### Providers Suportados
- **Google** (OpenID Connect)
- **Microsoft** (Azure AD / Personal)
- **GitHub**
- **Apple** (Sign in with Apple)

### Scopes Padrão
| Provider | Scopes |
|----------|--------|
| Google | `openid profile email` |
| Microsoft | `openid profile email User.Read` |
| GitHub | `read:user user:email` |
| Apple | `name email` |

### PKCE Obrigatório
- `code_challenge_method=S256`
- `code_verifier` gerado pelo cliente

---

## 📊 Rate Limiting (API)

| Tier | Requests/Minute | Burst |
|------|-----------------|-------|
| **Free** | 60 | 10 |
| **Starter** | 120 | 20 |
| **Professional** | 300 | 50 |
| **Enterprise** | 1000 | 200 |
| **Platform Admin** | Unlimited | - |

Headers de resposta:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1700000060
Retry-After: 45 (quando 429)
```

---

## 📝 OpenAPI Specification (Arquivo)

Localização: `docs/api/openapi.yaml` (ou `.json`)

Gerado automaticamente via:
```bash
# Via Scribe ou scramble
php artisan scribe:generate --format=openapi
```

---

**Versão:** 1.0  
**Formato:** OpenAPI 3.1  
**Owner:** CTO + API Integration Engineer  
**Validação:** Contract Tests (Pest Contracts) em CI