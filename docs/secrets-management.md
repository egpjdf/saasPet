# Secrets Management

## Overview

Saaspet uses a dual-layer secrets management approach:

1. **Development/CI**: 1Password CLI (`op inject`) for injecting secrets into GitHub Actions
2. **Production**: HashiCorp Vault with AppRole authentication for runtime secret injection

## Architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  GitHub Actions │────▶│   1Password CLI  │────▶│  Build/Deploy   │
│   (CI/CD)       │     │  (op inject)     │     │  (staging)      │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                                │
                                ▼
                       ┌──────────────────┐
                       │  HashiCorp Vault │
                       │  (Production)    │
                       └──────────────────┘
                                │
                    ┌───────────┴───────────┐
                    ▼                       ▼
            ┌───────────────┐       ┌───────────────┐
            │ Vault Agent   │       │ Laravel App   │
            │ (sidecar)     │──────▶│ (auto-reload) │
            └───────────────┘       └───────────────┘
```

## Secret Categories

| Category | Path | Description |
|----------|------|-------------|
| Database | `secret/saaspet/database` | PostgreSQL credentials |
| Redis | `secret/saaspet/redis` | Redis password |
| App | `secret/saaspet/app` | APP_KEY |
| JWT | `secret/saaspet/jwt` | JWT signing secret |
| Encryption | `secret/saaspet/encryption` | Laravel encryption key |
| Stripe | `secret/saaspet/stripe` | Stripe API keys + webhook secret |
| Paddle | `secret/saaspet/paddle` | Paddle API keys + webhook secret |
| Resend | `secret/saaspet/resend` | Resend API key |
| Sentry | `secret/saaspet/sentry` | Sentry DSN |
| Storage | `secret/saaspet/storage` | MinIO/R2 credentials |
| Reverb | `secret/saaspet/reverb` | Reverb credentials |
| Mail | `secret/saaspet/mail` | Mail from address/name |

## Tenant Secrets

Tenant-specific secrets are namespaced:

```
secret/saaspet/tenants/organizations/{org_id}
secret/saaspet/tenants/{org_id}/workspaces/{ws_id}
```

## 1Password CLI (Development/CI)

### Setup

1. Create 1Password service account
2. Store secrets in "Saaspet" vault
3. Add `OP_SERVICE_ACCOUNT_TOKEN` to GitHub repository secrets

### Usage in GitHub Actions

```yaml
- name: Configure 1Password
  run: echo "${{ secrets.OP_SERVICE_ACCOUNT_TOKEN }}" | op signin

- name: Inject secrets
  run: |
    op inject -i .env.template -o .env
```

### .env.template Example

```env
DB_PASSWORD=op://Saaspet/Database/password
STRIPE_SECRET=op://Saaspet/Stripe/secret_key
RESEND_API_KEY=op://Saaspet/Resend/api_key
```

## HashiCorp Vault (Production)

### Authentication

Production uses AppRole authentication:

```hcl
# Vault Policy for Saaspet
path "secret/data/saaspet/*" {
  capabilities = ["read", "list"]
}

path "secret/data/saaspet/tenants/*" {
  capabilities = ["read", "list"]
}
```

### Vault Agent Sidecar

The Vault Agent runs as a sidecar container:

```dockerfile
# docker-compose.production.yml
services:
  app:
    image: saaspet:latest
    environment:
      - VAULT_ENABLED=true
      - VAULT_ADDR=https://vault.example.com
    volumes:
      - vault-secrets:/home/vault/secrets

  vault-agent:
    image: hashicorp/vault-agent:latest
    volumes:
      - ./docker/vault-agent-config.hcl:/etc/vault/vault-agent-config.hcl
      - ./docker/vault/templates:/etc/vault/templates
      - vault-secrets:/home/vault/secrets
      - ./docker/vault/role_id:/etc/vault/role_id:ro
      - ./docker/vault/secret_id:/etc/vault/secret_id:ro
    depends_on:
      - app
```

### Auto-reload

When secrets change, Vault Agent rewrites template files and sends SIGHUP to PHP-FPM:

```hcl
template {
  source      = "/etc/vault/templates/secrets.tpl"
  destination = "/home/vault/secrets/.env.vault"
  command     = "pkill -HUP -f 'php-fpm' || true"
}
```

Laravel catches SIGHUP and reloads configuration:

```php
// bootstrap/app.php
if (config('vault.auto_reload.enabled')) {
    pcntl_signal(SIGHUP, function () {
        \App\Services\Vault\VaultClient::reloadConfig();
    });
}
```

## Secret Rotation

### Monthly Rotation (Automated)

GitHub Actions workflow runs on 1st of every month:

```yaml
# .github/workflows/secrets-rotation.yml
on:
  schedule:
    - cron: '0 2 1 * *'  # 2 AM UTC on 1st of month
```

Rotation script (`scripts/rotate-secrets.sh`):
1. Generates new secrets
2. Updates Vault
3. Updates 1Password (if configured)
4. Notifies team via Slack/Email

### Secrets Rotated

| Secret | Rotation Method | Manual Steps |
|--------|----------------|--------------|
| Database password | Vault + PG | Restart connections |
| Redis password | Vault + Redis | Restart connections |
| JWT secret | Vault | Invalidate tokens |
| Encryption key | Vault | Re-encrypt data |
| Stripe webhook | Vault + Dashboard | Update Stripe |
| Paddle webhook | Vault + Dashboard | Update Paddle |
| Resend API key | Vault + Dashboard | Update Resend |
| Sentry DSN | Vault + Dashboard | Update Sentry |

### Emergency Rotation

```bash
# Force immediate rotation
gh workflow run secrets-rotation.yml -f force=true
```

## Security Best Practices

### 1. No Secrets in Code

- ❌ Never commit `.env` files
- ❌ Never hardcode secrets in config files
- ✅ Use `.env.example` with placeholders
- ✅ Use `env()` only in config files

### 2. Least Privilege

- AppRole token has read-only access to `secret/saaspet/*`
- Separate policies for CI vs production
- Tenant isolation via Vault namespaces

### 3. Audit Trail

All Vault access is logged:

```bash
# View audit log
vault audit list
vault read sys/audit/log/file
```

### 4. Secret Scanning

CI pipeline includes secret scanning:

```yaml
- name: Secret scan
  run: |
    # Check for accidental commits
    git log --all --full-history --oneline -- '**/.env*'
    
    # Scan for patterns
    ! grep -r "sk_live_\|rk_live_\|AKIA" --include="*.php" app/ || exit 1
```

## Troubleshooting

### Vault Connection Issues

```bash
# Test connectivity
vault status -address=https://vault.example.com

# Test authentication
vault write auth/approle/login role_id=... secret_id=...
```

### Missing Secrets

```bash
# List available secrets
vault kv list secret/saaspet

# Read specific secret
vault kv get secret/saaspet/database
```

### Cache Issues

```bash
# Clear Vault cache
php artisan cache:clear --store=redis vault:*

# Or restart Vault Agent
docker restart vault-agent
```

## Compliance

### LGPD/GDPR

- Secrets containing personal data (webhook payloads) are encrypted at rest
- Access logs retained for 1 year
- Rotation logs retained for 3 years

### SOC 2

- All secrets managed through Vault
- Automated rotation with evidence
- Access reviews quarterly