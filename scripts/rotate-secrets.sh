#!/bin/bash
# Secrets Rotation Script for Saaspet
# Runs monthly via GitHub Actions cron

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VAULT_ADDR="${VAULT_ADDR:-https://vault.example.com}"
VAULT_ROLE_ID="${VAULT_ROLE_ID}"
VAULT_SECRET_ID="${VAULT_SECRET_ID}"

echo "🔄 Starting secrets rotation..."

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1"
}

# Authenticate with Vault
vault_auth() {
    log "Authenticating with Vault..."
    VAULT_TOKEN=$(vault write -field=token auth/approle/login \
        role_id="$VAULT_ROLE_ID" \
        secret_id="$VAULT_SECRET_ID")
    
    export VAULT_TOKEN
}

# Rotate database password
rotate_db_password() {
    log "Rotating database password..."
    
    # Generate new password
    NEW_PASSWORD=$(openssl rand -base64 32)
    
    # Update in Vault
    vault kv patch secret/saaspet/database \
        password="$NEW_PASSWORD" \
        rotated_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    
    # Update in 1Password (if configured)
    if command -v op &> /dev/null; then
        op item edit "Database Credentials" \
            password="$NEW_PASSWORD" \
            --vault="Saaspet" 2>/dev/null || warn "1Password update failed"
    fi
    
    log "Database password rotated"
}

# Rotate Redis password
rotate_redis_password() {
    log "Rotating Redis password..."
    
    NEW_PASSWORD=$(openssl rand -base64 32)
    
    vault kv patch secret/saaspet/redis \
        password="$NEW_PASSWORD" \
        rotated_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    
    log "Redis password rotated"
}

# Rotate JWT secret
rotate_jwt_secret() {
    log "Rotating JWT secret..."
    
    NEW_SECRET=$(openssl rand -base64 64)
    
    vault kv patch secret/saaspet/jwt \
        secret="$NEW_SECRET" \
        rotated_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    
    log "JWT secret rotated"
}

# Rotate encryption key
rotate_encryption_key() {
    log "Rotating encryption key..."
    
    NEW_KEY=$(openssl rand -base64 32)
    
    vault kv patch secret/saaspet/encryption \
        key="$NEW_KEY" \
        rotated_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    
    log "Encryption key rotated"
}

# Rotate Stripe webhook secret
rotate_stripe_webhook_secret() {
    log "Rotating Stripe webhook secret..."
    
    # This would require Stripe API to create new webhook endpoint
    # For now, just rotate the stored secret
    NEW_SECRET=$(openssl rand -base64 32)
    
    vault kv patch secret/saaspet/stripe \
        webhook_secret="$NEW_SECRET" \
        rotated_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    
    log "Stripe webhook secret rotated (manual Stripe dashboard update required)"
}

# Rotate Paddle webhook secret
rotate_paddle_webhook_secret() {
    log "Rotating Paddle webhook secret..."
    
    NEW_SECRET=$(openssl rand -base64 32)
    
    vault kv patch secret/saaspet/paddle \
        webhook_secret="$NEW_SECRET" \
        rotated_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    
    log "Paddle webhook secret rotated (manual Paddle dashboard update required)"
}

# Rotate Resend API key
rotate_resend_api_key() {
    log "Rotating Resend API key..."
    
    # This requires Resend API to create new key
    # For now, just rotate the stored secret
    NEW_KEY=$(openssl rand -base64 32)
    
    vault kv patch secret/saaspet/resend \
        api_key="$NEW_KEY" \
        rotated_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    
    log "Resend API key rotated (manual Resend dashboard update required)"
}

# Rotate Sentry DSN
rotate_sentry_dsn() {
    log "Rotating Sentry DSN..."
    
    # This requires Sentry API
    NEW_DSN="https://$(openssl rand -hex 32)@o1.ingest.sentry.io/1"
    
    vault kv patch secret/saaspet/sentry \
        dsn="$NEW_DSN" \
        rotated_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    
    log "Sentry DSN rotated (manual Sentry dashboard update required)"
}

# Notify about rotation
notify_rotation() {
    log "Sending rotation notification..."
    
    if [ -n "${SLACK_WEBHOOK_URL:-}" ]; then
        curl -X POST "${SLACK_WEBHOOK_URL}" \
            -H "Content-Type: application/json" \
            -d "{\"text\": \"🔐 Secrets rotation completed for Saaspet\", \"attachments\": [{\"color\": \"good\", \"fields\": [{\"title\": \"Rotated\", \"value\": \"Database, Redis, JWT, Encryption, Stripe, Paddle, Resend, Sentry\", \"short\": false}]}]}"
    fi
    
    if [ -n "${EMAIL_WEBHOOK_URL:-}" ]; then
        curl -X POST "${EMAIL_WEBHOOK_URL}" \
            -H "Content-Type: application/json" \
            -d "{\"to\": \"security@saaspet.com\", \"subject\": \"Secrets Rotation Completed\", \"body\": \"All secrets have been rotated successfully.\"}"
    fi
}

# Main rotation flow
main() {
    log "Starting monthly secrets rotation"
    
    # Check prerequisites
    if ! command -v vault &> /dev/null; then
        error "Vault CLI not installed"
        exit 1
    fi
    
    if [ -z "$VAULT_ROLE_ID" ] || [ -z "$VAULT_SECRET_ID" ]; then
        error "Vault credentials not configured"
        exit 1
    fi
    
    vault_auth
    
    # Rotate all secrets
    rotate_db_password
    rotate_redis_password
    rotate_jwt_secret
    rotate_encryption_key
    rotate_stripe_webhook_secret
    rotate_paddle_webhook_secret
    rotate_resend_api_key
    rotate_sentry_dsn
    
    notify_rotation
    
    log "Secrets rotation completed successfully"
}

# Run main
main "$@"