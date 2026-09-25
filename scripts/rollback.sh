#!/bin/bash
# Rollback Script for Saaspet Deployment
# Usage: ./scripts/rollback.sh <environment> [version]

set -euo pipefail

ENVIRONMENT="${1:-staging}"
VERSION="${2:-previous}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "🔄 Rolling back $ENVIRONMENT to $VERSION"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Load environment variables
if [ -f "$SCRIPT_DIR/../.env.$ENVIRONMENT" ]; then
    source "$SCRIPT_DIR/../.env.$ENVIRONMENT"
fi

rollback_laravel_cloud() {
    echo "Rolling back via Laravel Cloud..."
    curl -X POST "https://api.laravel.cloud/v1/projects/${LARAVEL_CLOUD_PROJECT_ID}/deployments/rollback" \
        -H "Authorization: Bearer ${LARAVEL_CLOUD_API_TOKEN}" \
        -H "Content-Type: application/json"
}

rollback_forge() {
    echo "Rolling back via Laravel Forge..."
    local server_id="${FORGE_${ENVIRONMENT^^}_SERVER_ID}"
    local site_id="${FORGE_${ENVIRONMENT^^}_SITE_ID}"
    
    curl -X POST "https://forge.laravel.com/api/v1/servers/${!server_id}/sites/${!site_id}/deploy" \
        -H "Authorization: Bearer ${FORGE_API_TOKEN}" \
        -H "Content-Type: application/json" \
        -d '{"commit": "previous"}'
}

rollback_vapor() {
    echo "Rolling back via Laravel Vapor..."
    vapor rollback "$ENVIRONMENT"
}

rollback_docker() {
    echo "Rolling back via Docker..."
    local image_tag="ghcr.io/${GITHUB_REPOSITORY}/saaspet:${ENVIRONMENT}-${VERSION}"
    
    # Pull previous image
    docker pull "$image_tag"
    
    # Update docker-compose to use previous image
    sed -i "s|image: .*|image: $image_tag|" docker-compose.${ENVIRONMENT}.yml
    
    # Restart services
    docker-compose -f docker-compose.${ENVIRONMENT}.yml up -d --force-recreate
}

# Determine deployment platform and rollback
if [ -n "${LARAVEL_CLOUD_API_TOKEN:-}" ]; then
    rollback_laravel_cloud
elif [ -n "${FORGE_API_TOKEN:-}" ]; then
    rollback_forge
elif [ -n "${VAPOR_API_TOKEN:-}" ]; then
    rollback_vapor
else
    echo "No deployment platform configured, attempting Docker rollback..."
    rollback_docker
fi

# Verify rollback
echo "Verifying rollback..."
sleep 10

if curl -sf "${!ENVIRONMENT^^}_URL/health" > /dev/null; then
    echo -e "${GREEN}✓ Rollback verified - health check passing${NC}"
else
    echo -e "${RED}✗ Rollback verification failed${NC}"
    exit 1
fi

# Notify
if [ -n "${SLACK_WEBHOOK_URL:-}" ]; then
    curl -X POST "${SLACK_WEBHOOK_URL}" \
        -H "Content-Type: application/json" \
        -d "{\"text\": \"🔄 Rollback completed for $ENVIRONMENT to $VERSION\"}"
fi

echo -e "${GREEN}Rollback completed successfully${NC}"