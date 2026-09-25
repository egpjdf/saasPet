# Definition of Done (DoD) - Saaspet

> **Regra de Ouro:** Nada vai para `main` sem passar em **TODOS** os gates abaixo. O CTO valida, o CO aprova.

---

## ✅ Código & Arquitetura

| Item | Critério | Validação |
|------|----------|-----------|
| **Code Style** | Laravel Pint (PSR-12) passing | `vendor/bin/pint --test` |
| **Static Analysis** | PHPStan Level 5 - 0 erros, 0 warnings | `vendor/bin/phpstan analyse --level=5` |
| **Type Safety** | Zero `any`, zero `mixed` sem justificativa documentada | PHPStan + code review |
| **PHP Attributes** | `#[Middleware]`, `#[Authorize]`, `#[Tries]`, `#[Backoff]`, `#[Timeout]` usados | Code review |
| **Naming** | PSR-12 + convenções Laravel (Models singular, Controllers plural) | Code review |
| **ADR** | Decisões arquiteturais documentadas em `docs/architecture/adr/` | CTO review |

---

## 🏢 Multi-Tenancy (OBRIGATÓRIO - Bloqueia merge se falhar)

| Item | Critério | Validação |
|------|----------|-----------|
| **Migration** | `organization_id` + `workspace_id` + FKs + índices compostos | `tenant-guardian` check |
| **RLS Policy** | Policy `organization_isolation` + `workspace_isolation` ativa no PostgreSQL | `tenant-guardian` + `db-architect` |
| **Global Scopes** | `BelongsToOrganization` + `BelongsToWorkspace` ativos no Model | `tenant-guardian` check |
| **Policies/Gates** | Valida `$model->organization_id === auth()->orgId()` E `workspace_id` | `tenant-guardian` + `security-auditor` |
| **Jobs/Queue** | Implementa `HasTenantContext` (serializa org_id + ws_id) | `tenant-guardian` check |
| **Cache Keys** | Prefixo `tenant:{org_id}:workspace:{ws_id}:` | `tenant-guardian` check |
| **Events** | Listeners propagam `app('tenant')->setOrganization()/setWorkspace()` | `tenant-guardian` check |
| **JSON:API** | Resources filtram por tenant automaticamente (global scope) | `tenant-guardian` check |
| **Vector Search** | `whereVectorSimilarTo` escopado por `organization_id` + `workspace_id` | `tenant-guardian` check |
| **AI SDK** | Agents recebem `withContext(['organization_id', 'workspace_id'])` | `tenant-guardian` check |

---

## 🔒 Segurança

| Item | Critério | Validação |
|------|----------|-----------|
| **Security Audit** | `security-auditor` aprova (0 findings críticos/altos) | `security-auditor` report |
| **Secrets** | Apenas em `.env` / 1Password / Vault (nunca no código) | `security-auditor` + `trufflehog` |
| **Rate Limiting** | 60/min API, 10/min auth, 5/min sensitive endpoints | Config + testes |
| **Audit Log** | Login, CRUD sensível, permission changes, billing, impersonation | `AuditLog` entries |
| **CSRF/CORS** | `PreventRequestForgery` ativo, CORS configurado | Config review |
| **Security Headers** | CSP, HSTS, X-Frame-Options, Referrer-Policy | `security-auditor` check |
| **Dependency Scan** | 0 CVEs critical/alta em produção | `composer audit` + GitHub Dependabot |
| **Webhook Signatures** | HMAC SHA256 validado em TODOS webhooks inbound/outbound | `api-integration-engineer` |
| **OAuth** | State parameter, PKCE, token refresh seguro | `api-integration-engineer` |

---

## 🧪 Testes

| Item | Critério | Validação |
|------|----------|-----------|
| **Coverage** | ≥ 85% (unit + feature) | `vendor/bin/pest --coverage --min=85` |
| **Mutation Score** | MSI ≥ 70%, Covered MSI ≥ 60% | `vendor/bin/infection --min-msi=70` |
| **Contract Tests** | 100% endpoints API públicos cobertos (Pest Contracts) | `qa-engineer` |
| **Tenant Isolation** | Cross-tenant: Org A ≠ Org B (403), WS A ≠ WS B (403) | `qa-engineer` + `tenant-guardian` |
| **Browser Tests** | Dusk: Login, Onboarding, Billing, Admin critical paths | `vendor/bin/pest tests/Browser` |
| **Performance** | p95 < 200ms (Octane + k6 baseline) | `qa-engineer` |
| **Webhook Tests** | Delivery, retry, DLQ, signature verification | `api-integration-engineer` |
| **OAuth Tests** | Flows completos (mock providers) | `api-integration-engineer` |

---

## 📋 Documentação & Deploy

| Item | Critério | Validação |
|------|----------|-----------|
| **OpenAPI/Swagger** | Atualizado para endpoints novos/modificados | `docs/api/` |
| **README Módulo** | `docs/modules/{feature}.md` com overview, usage, config | `docs-writer` |
| **CHANGELOG** | Entry no `CHANGELOG.md` (Keep a Changelog format) | `release-manager` |
| **Migration Test** | Testada em staging + rollback verificado | `devops-engineer` |
| **Feature Flag** | Se breaking change → feature flag obrigatória | CTO decision |
| **Runbook** | Rollback plan documentado em `docs/runbooks/` | `devops-engineer` |

---

## ⚖️ Compliance (LGPD/GDPR)

| Item | Critério | Validação |
|------|----------|-----------|
| **Data Export** | Funciona para user/org/ws (JSON + PDF) | `compliance-officer` |
| **Data Deletion** | Anonimiza audit logs, deleta dados pessoais, queue 3rd party | `compliance-officer` |
| **Consent** | Granular por propósito, versionado, revogável | `compliance-officer` |
| **Cookie Banner** | Categorias: essential/analytics/marketing, opt-in | `compliance-officer` |
| **Retention** | Políticas configuradas e job de limpeza ativo | `compliance-officer` |
| **DPA** | Assinado por Organization, versionado, renovável | `compliance-officer` |
| **Breach Plan** | Notificação ANPD (2 dias) + usuários se risco alto | `compliance-officer` |

---

## 🚀 Gates de Merge (Automatizados no GitHub Actions)

```yaml
# Todos DEVEM passar para merge em main/develop
required_status_checks:
  - "Static Analysis (PHPStan + Pint)"
  - "Unit & Feature Tests (Pest)"
  - "Mutation Testing (Infection)"
  - "Browser Tests (Dusk)"
  - "Tenant Isolation Tests"
  - "Security Scan (Secrets + Dependencies)"
  - "Build Docker Image"
```

---

## 📝 Checklist de PR (Preenchido pelo Autor)

```markdown
## PR Checklist

### Código
- [ ] Pint passing
- [ ] PHPStan Level 5 passing
- [ ] Tipagem estrita (sem `any`/`mixed`)
- [ ] PHP Attributes usadas
- [ ] ADR criado/atualizado se decisão arquitetural

### Multi-Tenancy
- [ ] Migration tem org_id + ws_id + FKs + índices
- [ ] RLS policy criada no SQL
- [ ] Global scopes ativos no Model
- [ ] Policy valida posse (org + ws)
- [ ] Job implementa HasTenantContext
- [ ] Cache keys têm prefixo tenant
- [ ] Event listeners propagam contexto
- [ ] Vector search escopado por tenant
- [ ] AI agents recebem tenant context

### Segurança
- [ ] Security auditor aprovou
- [ ] Sem secrets no código
- [ ] Rate limiting configurado
- [ ] Audit log implementado
- [ ] Webhook signatures validadas

### Testes
- [ ] Coverage ≥ 85%
- [ ] Mutation MSI ≥ 70%
- [ ] Contract tests passing
- [ ] Tenant isolation tests passing
- [ ] Dusk tests passing (se UI)

### Documentação
- [ ] OpenAPI atualizado
- [ ] CHANGELOG entry
- [ ] README módulo (se novo)

### Compliance
- [ ] Data export testado
- [ ] Data deletion testado
- [ ] Consent flow testado
- [ ] Cookie banner respeitado
```

---

## 🎯 Exceções (Apenas com Aprovação Escrita do CTO + CO)

| Situação | Processo |
|----------|----------|
| Hotfix crítico em produção | CTO aprova, CO notificado, DoD relaxado temporariamente, follow-up ticket criado |
| Prova de conceito (spike) | Marcado como `spike`, não merge em main, documentado em ADR |
| Dependência externa bloqueando | Documentado, workaround, SLA de resolução acordado |

---

**Versão:** 1.0  
**Última atualização:** 2024-09-22  
**Aprovado por:** CTO + CO  
**Próxima revisão:** Sprint 1 Review