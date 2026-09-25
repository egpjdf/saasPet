# Definition of Ready (DoR) - Saaspet

> **Regra:** Story NÃO entra no Sprint Backlog sem atender TODOS os critérios abaixo.

---

## ✅ Critérios Obrigatórios

### 1. Clareza e Escopo
- [ ] **Título claro:** Formato "Como [persona], quero [ação], para [benefício]"
- [ ] **Descrição completa:** O que, por que, para quem
- [ ] **Critérios de Aceite (AC):** Lista verificável, testável, não ambígua
- [ ] **Escopo delimitado:** O que NÃO está incluído (out of scope)

### 2. Dependências Resolvidas
- [ ] **Design/UX:** Figma/link aprovado pelo CO (se UI)
- [ ] **Arquitetura:** ADR aprovado pelo CTO (se nova decisão)
- [ ] **API Contract:** OpenAPI spec definido (se novo endpoint)
- [ ] **Data Model:** Migration draft revisada pelo DB Architect
- [ ] **Segurança:** Threat model revisado pelo Security Auditor (se sensível)
- [ ] **Compliance:** DPIA aprovado pelo Compliance Officer (se dados sensíveis/IA)

### 3. Estimativa e Tamanho
- [ ] **Estimada em Story Points:** Planning Poker com team
- [ ] **Tamanho adequado:** ≤ 8 pontos (se > 8, quebrar em stories menores)
- [ ] **Definição de "Done" clara:** Referência ao DoD do projeto

### 4. Testabilidade
- [ ] **Cenários de teste identificados:** Happy path, edge cases, error cases
- [ ] **Tenant isolation scenarios:** Cross-org, cross-ws, cross-user
- [ ] **Dados de teste:** Factories/seeders necessários identificados
- [ ] **Automatizável:** Testes podem ser escritos em Pest/Dusk

### 5. Multi-Tenancy (Obrigatório para TODAS stories que tocam dados)
- [ ] **Organization scope definido:** Como a feature isola por Organization
- [ ] **Workspace scope definido:** Como a feature isola por Workspace
- [ ] **RLS impact:** Migration inclui RLS policy? (DB Architect confirma)
- [ ] **Global scope impact:** Model precisa de novo trait/scope?
- [ ] **Policy/Gate:** Novas policies necessárias?
- [ ] **Cache/Queue/Event:** Tenant context propagado?

### 6. Recursos Disponíveis
- [ ] **Agente responsável identificado:** Backend/Frontend/DB/AI/DevOps/etc
- [ ] **Capacidade no sprint:** Team tem bandwidth
- [ ] **Ambiente:** Staging/local disponível para teste

---

## 📋 Template de Story (Obrigatório)

```markdown
## US-XXX: [Título no formato "Como..., quero..., para..."]

### Descrição
[Contexto, motivação, regras de negócio]

### Critérios de Aceite
1. [AC1 - verificável]
2. [AC2 - verificável]
3. [AC3 - verificável]

### Fora de Escopo
- [Item 1]
- [Item 2]

### Multi-Tenancy
- **Organization Isolation:** [Como isola]
- **Workspace Isolation:** [Como isola]
- **RLS Policy:** [Sim/Não - migration inclui?]
- **Global Scopes:** [Quais traits/scopes]
- **Policies:** [Quais policies novas]
- **Tenant Context:** [Jobs/Cache/Events afetados]

### Dependências
- Design: [Link Figma / N/A]
- ADR: [Link / N/A]
- API Spec: [Link / N/A]
- Migration Draft: [Link / N/A]
- Security Review: [Aprovado / Pendente / N/A]
- DPIA: [Aprovado / Pendente / N/A]

### Estimativa
- **Story Points:** [1, 2, 3, 5, 8]
- **Agente Responsável:** [backend-dev / frontend-dev / db-architect / etc]

### Testes
- **Cenários:** [Lista]
- **Tenant Isolation Tests:** [Cenários cross-tenant]
- **Dados Necessários:** [Factories/Seeders]

### Notas Técnicas
[Observações, riscos, decisões técnicas]
```

---

## 🚫 Anti-Patterns (Story NÃO Pronta)

| Anti-Pattern | Exemplo | Correção |
|--------------|---------|----------|
| **Vaga** | "Sistema de relatórios" | "Como Org Admin, quero exportar relatório de vendas em CSV, para analisar performance" |
| **Sem AC** | "Funcionar bem" | "CSV tem colunas X,Y,Z; filtra por data; retorna 403 se cross-tenant" |
| **Grande demais** | 13 pontos | Quebrar: "Export CSV" (5) + "Filtros avançados" (5) + "Agendamento" (3) |
| **Sem multi-tenancy** | Esquece org_id | Preencher seção Multi-Tenancy obrigatoriamente |
| **Dependência bloqueada** | "Aguardando design" | Não entra no sprint até design aprovado |
| **Não testável** | "Performance melhorada" | "p95 < 200ms no endpoint X com 10k registros" |

---

## 🔄 Processo de Refinement

1. **Backlog Refinement (Semanal):** CTO + Agents revisam top 10 do backlog
2. **DoR Check:** Cada story verificada contra esta lista
3. **Estimativa:** Planning Poker assíncrono ou sync
4. **Sprint Planning:** CO prioriza, CTO confirma capacity, stories movidas para Sprint Backlog

---

**Versão:** 1.0  
**Última atualização:** 2024-09-22  
**Aprovado por:** CTO + CO