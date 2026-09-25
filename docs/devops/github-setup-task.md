# Task: GitHub Repository Setup & Branch Protection

**Assignee:** `devops-engineer`
**Priority:** P0 - Crítico (infraestrutura base)
**Pré-requisito:** Repo criado em https://github.com/egpjdf/saasPet
**Autorização:** **CTO DEVE PERGUNTAR AO CO ANTES DE EXECUTAR QUALQUER SINCRONIZAÇÃO COM GITHUB**

---

## Objetivo

Configurar repositório Git local, conectar ao GitHub remote, configurar branch protection na `main` conforme regras obrigatórias, e validar CI/CD pipeline.

---

## Especificação da Task

### 1. Git Local Init + Commit Inicial

```bash
# Verificar se já existe .git
if [ ! -d .git ]; then
    git init
    git config user.name "Saaspet Bot"
    git config user.email "bot@saaspet.com"
fi

# .gitignore (verificar se existe, senão criar)
# Adicionar todos os arquivos do projeto
git add .
git commit -m "chore: initial commit - Sprint 0 complete + Sprint 1 Wave 1

- Multi-tenancy: RLS + Global Scopes + Policies + Middleware
- Auth: Sanctum + Fortify + 2FA
- Billing: Cashier Stripe + Paddle
- Notifications: Resend + Reverb + Preferences
- LGPD: Consent, Export, Deletion, Retention
- CI/CD: GitHub Actions (CI, CD Staging/Prod, Secrets Rotation)
- Frontend: Inertia/Vue/TS/Tailwind/Pinia (ORG-02, ORG-05)
- Tests: Pest + Dusk + Infection (cross-tenant isolation)
"
```

### 2. Remote + Push Branches

```bash
# Adicionar remote
git remote add origin https://github.com/egpjdf/saasPet.git

# Criar e pushar branch develop
git checkout -b develop
git push -u origin develop

# Push main (após branch protection configurada via API)
git checkout main
git push -u origin main
```

### 3. Branch Protection via GitHub API (OBRIGATÓRIO)

```bash
# Usar gh CLI (precisa estar autenticado: gh auth login)
# Ou curl com token

# Regras para main:
gh api repos/egpjdf/saasPet/branches/main/protection \
  --method PUT \
  --field required_status_checks='{"strict":true,"contexts":["ci"]}' \
  --field enforce_admins=true \
  --field required_pull_request_reviews='{"required_approving_review_count":1,"dismiss_stale_reviews":true,"require_code_owner_reviews":false}' \
  --field restrictions=null \
  --field allow_force_pushes=false \
  --field allow_deletions=false \
  --field required_linear_history=true \
  --field allow_auto_merge=false \
  --field required_conversation_resolution=true
```

**Regras Aplicadas:**
- ✅ No direct push to main
- ✅ Require PR for merge
- ✅ Require 1 approval
- ✅ Require CI/tests pass (context: "ci")
- ✅ Require branch up to date
- ✅ No force push
- ✅ No deletion
- ✅ Linear history
- ✅ Conversation resolution required

### 4. Branch Protection para develop (Opcional - Menos Restritiva)

```bash
gh api repos/egpjdf/saasPet/branches/develop/protection \
  --method PUT \
  --field required_status_checks='{"strict":true,"contexts":["ci"]}' \
  --field enforce_admins=false \
  --field required_pull_request_reviews='{"required_approving_review_count":1,"dismiss_stale_reviews":true}' \
  --field restrictions=null \
  --field allow_force_pushes=false \
  --field allow_deletions=false
```

### 5. Environments no GitHub

```bash
# Criar environments com regras
gh api repos/egpjdf/saasPet/environments/staging --method PUT \
  --field wait_timer=0 \
  --field reviewers='[]' \
  --field deployment_branch_policy='{"protected_branches":true,"custom_branch_policies":false}'

gh api repos/egpjdf/saasPet/environments/production --method PUT \
  --field wait_timer=5 \
  --field reviewers='[{"type":"User","id":<SEU_USER_ID>}]' \
  --field deployment_branch_policy='{"protected_branches":true,"custom_branch_policies":false}'
```

### 6. Secrets & Variables no GitHub

```bash
# Secrets para CI/CD (definir via gh ou UI)
gh secret set STRIPE_SECRET_KEY --body "<valor>" --repo egpjdf/saasPet
gh secret set PADDLE_API_KEY --body "<valor>" --repo egpjdf/saaspet
gh secret set RESEND_API_KEY --body "<valor>" --repo egpjdf/saaspet
gh secret set SENTRY_DSN --body "<valor>" --repo egpjdf/saaspet
gh secret set VAULT_ADDR --body "<valor>" --repo egpjdf/saaspet
gh secret set VAULT_TOKEN --body "<valor>" --repo egpjdf/saaspet
gh secret set GH_TOKEN --body "<valor>" --repo egpjdf/saaspet
# ... outras secrets do .env.example

# Variables (não secretas)
gh variable set APP_ENV --body "production" --repo egpjdf/saaspet
gh variable set APP_URL --body "https://saaspet.com" --repo egpjdf/saaspet
```

### 7. Deploy Keys para CI/CD (se usar SSH)

```bash
# Gerar chave SSH para CI
ssh-keygen -t ed25519 -f ~/.ssh/saaspet_ci -N ""
# Adicionar chave pública como Deploy Key no repo (Settings > Deploy keys > Add deploy key)
# Permitir write access para push tags/releases
```

### 8. Validar CI/CD Pipeline

```bash
# Trigger CI manualmente
gh workflow run ci.yml --repo egpjdf/saaspet --ref develop

# Verificar status
gh run list --repo egpjdf/saaspet --limit 5

# Verificar se branch protection funciona
# Tentar push direto na main (deve falhar):
git checkout main
echo "test" > test.txt
git add test.txt
git commit -m "test: direct push should fail"
git push origin main  # DEVE FALHAR: "protected branch"
```

---

## Checklist de Validação (CO deve confirmar)

| Item | Status | Evidência |
|------|--------|-----------|
| Git init + commit inicial | [ ] | `git log --oneline -1` |
| Remote origin configurado | [ ] | `git remote -v` |
| Branch `develop` pushada | [ ] | `git branch -r` |
| Branch `main` pushada | [ ] | `git branch -r` |
| Branch protection `main` ativa | [ ] | GitHub UI: Settings > Branches |
| Required status checks: `ci` | [ ] | GitHub UI |
| Required 1 approval | [ ] | GitHub UI |
| Require branch up to date | [ ] | GitHub UI |
| No force push | [ ] | GitHub UI |
| No deletion | [ ] | GitHub UI |
| Linear history | [ ] | GitHub UI |
| Conversation resolution | [ ] | GitHub UI |
| Environments: staging/production | [ ] | GitHub UI: Settings > Environments |
| Secrets configuradas | [ ] | GitHub UI: Settings > Secrets |
| CI pipeline roda no PR | [ ] | Abrir PR teste |
| Push direto na main falha | [ ] | Testar e confirmar erro |

---

## Arquivos de Referência

- `.github/workflows/ci.yml` - Pipeline CI (já existe)
- `.github/workflows/cd-staging.yml` - Deploy staging (já existe)
- `.github/workflows/cd-production.yml` - Deploy production (já existe)
- `.github/workflows/secrets-rotation.yml` - Rotação secrets (já existe)
- `.gitignore` - Verificar se cobre: `.env`, `vendor/`, `node_modules/`, `storage/`, `bootstrap/cache/`, `*.log`, `.phpunit.result.cache`, `.pest.php`, `coverage/`, `.php-cs-fixer.cache`

---

## Ordem de Execução (devops-engineer)

1. [ ] **Verificar auth gh CLI** (`gh auth status`)
2. [ ] **Git init + commit** (se necessário)
3. [ ] **Remote add + push develop**
4. [ ] **Configurar branch protection main via API**
5. [ ] **Configurar branch protection develop (opcional)**
6. [ ] **Criar environments staging/production**
7. [ ] **Configurar secrets/variables**
8. [ ] **Push main (após protection ativa)**
9. [ ] **Validar CI roda em PR teste**
10. [ ] **Testar push direto na main (deve falhar)**
11. [ ] **Reportar ao CTO com checklist preenchido**

---

## ⚠️ Regras de Segurança (OBRIGATÓRIO)

- **NUNCA** commitar secrets no repositório
- **SEMPRE** usar `gh secret set` para secrets
- **SEMPRE** validar branch protection antes de push na main
- **CTO DEVE PERGUNTAR AO CO** antes de qualquer `git push origin main` ou alteração de branch protection
- Secrets sensíveis (Stripe, Paddle, Vault, Sentry) **NUNCA** no código

---

## Ponto de Retomada

Se interrompido, retomar pelo último item não concluído no checklist acima.

---

**Arquivo:** `docs/devops/github-setup-task.md`
**Pronto para execução quando CO autorizar.**