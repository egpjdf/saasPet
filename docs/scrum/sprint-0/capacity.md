# Sprint 0 Capacity Planning

## Team Capacity (3 dias = 24h úteis por dev)

| Agente | Disponibilidade | Foco Principal |
|--------|----------------|----------------|
| devops-engineer | 100% (24h) | ENV-04, ENV-14, ENV-17, ENV-18, ENV-19, ENV-20 |
| backend-dev | 100% (24h) | ENV-05, ENV-07, ENV-08, ENV-09, ENV-10, ENV-11 |
| frontend-dev | 100% (24h) | ENV-16 |
| db-architect | 100% (24h) | ENV-05, ENV-06 |
| billing-engineer | 50% (12h) | ENV-15 |
| notification-engineer | 50% (12h) | ENV-22 |
| api-integration-engineer | 50% (12h) | ENV-23 |
| compliance-officer | 50% (12h) | ENV-21 |
| qa-engineer | 50% (12h) | ENV-12 |
| security-auditor | 25% (6h) | ENV-12, reviews |
| docs-writer | 25% (6h) | ENV-13 |

**Total Capacity:** ~138h equivalent
**Target Delivery:** 53 pts (Sprint 0 remaining)

## Parallel Execution Waves

### Wave 1 (Dia 1 - Manhã): Foundation & Database
**Agents:** devops-engineer, db-architect, backend-dev (parallel)
- ENV-04: GitHub Actions CI (devops) - continue
- ENV-05: Models + Traits (backend + db-architect)
- ENV-06: Migrations + RLS (db-architect) - depends on ENV-05

### Wave 2 (Dia 1 - Tarde): Middleware & Auth
**Agents:** backend-dev, devops-engineer (parallel)
- ENV-07: Middleware pipeline (backend)
- ENV-08: Global Scopes (backend) - parallel with ENV-07
- ENV-11: Sanctum + Fortify (backend) - can start after ENV-05
- ENV-14: Pulse + Telescope + Sentry (devops)
- ENV-17: MinIO Config (devops)
- ENV-18: Dockerfile multi-stage (devops)

### Wave 3 (Dia 2): Policies, Resolver, Frontend
**Agents:** backend-dev, frontend-dev, billing, notification, api, compliance (parallel)
- ENV-09: Policies + Gates (backend) - after ENV-07
- ENV-10: Tenant Resolver (backend) - after ENV-07
- ENV-16: Reverb + Echo + Vue Composables (frontend + backend)
- ENV-15: Cashier Stripe (billing)
- ENV-22: Notification Resend (notification)
- ENV-23: Webhook out + OAuth (api-integration)
- ENV-21: LGPD Consent/Export/Deletion (compliance)

### Wave 4 (Dia 3): CI/CD, Tests, Docs, Security Review
**Agents:** devops, qa, security, docs, all (parallel)
- ENV-04: Complete CI (devops)
- ENV-19: CD Pipeline (devops) - after ENV-04
- ENV-20: Secrets Integration (devops)
- ENV-12: Cross-Tenant Tests (qa + security) - after ENV-06, ENV-08
- ENV-13: ADR-001 (docs) - after ENV-06
- Final tenant-guardian validation
- Final security-auditor review

## Dependency Graph

```
ENV-01 → ENV-02 → ENV-03
    ↓         ↓         ↓
ENV-14    ENV-05 ← ENV-02    ENV-16
ENV-17    ENV-11 ← ENV-02
ENV-18    ENV-06 ← ENV-05
ENV-20    ENV-07 ← ENV-05
         ENV-08 ← ENV-05
         ENV-09 ← ENV-07
         ENV-10 ← ENV-07
         ENV-15 ← ENV-02
         ENV-22 ← ENV-02
         ENV-23 ← ENV-02
         ENV-21 ← ENV-05
         ENV-12 ← ENV-06, ENV-08
         ENV-13 ← ENV-06
         ENV-19 ← ENV-04
```

## Risk Mitigation

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| RLS policies complexas | Alta | Alto | db-architect foca 100% nisso Dia 1 |
| Cross-tenant tests falham | Média | Crítico | qa + security pair no Dia 3 |
| CI pipeline flaky | Média | Alto | devops valida local primeiro |
| Tenant leaks | Baixa | Crítico | tenant-guardian obrigatório em cada PR |

## Daily Standup Schedule
- **Horário:** 09:00 (async via chat)
- **Formato:** Done / Doing / Blockers
- **CTO consolida** e reporta ao CO