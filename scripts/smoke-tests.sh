#!/bin/bash
# Smoke Tests for Saaspet Deployment
# Usage: ./scripts/smoke-tests.sh <BASE_URL> [TOKEN]

set -euo pipefail

BASE_URL="${1:-http://localhost}"
TOKEN="${2:-}"
EXIT_CODE=0

echo "🔍 Running smoke tests against: $BASE_URL"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

pass() {
    echo -e "${GREEN}✓${NC} $1"
}

fail() {
    echo -e "${RED}✗${NC} $1"
    EXIT_CODE=1
}

warn() {
    echo -e "${YELLOW}⚠${NC} $1"
}

# 1. Health Check
echo ""
echo "=== Health Checks ==="
if curl -sf "$BASE_URL/health" > /dev/null; then
    pass "Health endpoint responding"
else
    fail "Health endpoint not responding"
fi

# 2. Health Check - Detailed
HEALTH_RESPONSE=$(curl -s "$BASE_URL/health" || echo "{}")
if echo "$HEALTH_RESPONSE" | grep -q '"status":"ok"'; then
    pass "Health status OK"
else
    fail "Health status not OK: $HEALTH_RESPONSE"
fi

# 3. Database Health
if curl -sf "$BASE_URL/health/database" > /dev/null; then
    pass "Database health check"
else
    warn "Database health endpoint not available"
fi

# 4. Redis Health
if curl -sf "$BASE_URL/health/redis" > /dev/null; then
    pass "Redis health check"
else
    warn "Redis health endpoint not available"
fi

# 5. Queue Health
if curl -sf "$BASE_URL/health/queue" > /dev/null; then
    pass "Queue health check"
else
    warn "Queue health endpoint not available"
fi

# 6. Migration Status
if curl -sf "$BASE_URL/health/migrations" > /dev/null; then
    pass "Migrations health check"
else
    warn "Migrations health endpoint not available"
fi

# 7. Reverb Health
if curl -sf "$BASE_URL/reverb/health" > /dev/null; then
    pass "Reverb health check"
else
    warn "Reverb health endpoint not available"
fi

# 8. Auth Pages
echo ""
echo "=== Auth Pages ==="
if curl -sf "$BASE_URL/login" | grep -q "Login"; then
    pass "Login page loads"
else
    fail "Login page not loading"
fi

if curl -sf "$BASE_URL/register" | grep -q "Register"; then
    pass "Register page loads"
else
    fail "Register page not loading"
fi

# 9. Tenant Resolution
echo ""
echo "=== Tenant Resolution ==="
# Test org-level route
ORG_RESPONSE=$(curl -s -w "%{http_code}" -o /dev/null "$BASE_URL/org-a/dashboard" || echo "000")
if [[ "$ORG_RESPONSE" == "200" || "$ORG_RESPONSE" == "302" ]]; then
    pass "Organization route resolves (HTTP $ORG_RESPONSE)"
else
    fail "Organization route failed (HTTP $ORG_RESPONSE)"
fi

# Test workspace-level route
WS_RESPONSE=$(curl -s -w "%{http_code}" -o /dev/null "$BASE_URL/org-a/ws-a1/dashboard" || echo "000")
if [[ "$WS_RESPONSE" == "200" || "$WS_RESPONSE" == "302" ]]; then
    pass "Workspace route resolves (HTTP $WS_RESPONSE)"
else
    fail "Workspace route failed (HTTP $WS_RESPONSE)"
fi

# Test invalid org returns 404
INVALID_ORG=$(curl -s -w "%{http_code}" -o /dev/null "$BASE_URL/invalid-org/dashboard" || echo "000")
if [[ "$INVALID_ORG" == "404" ]]; then
    pass "Invalid organization returns 404"
else
    warn "Invalid organization returned HTTP $INVALID_ORG (expected 404)"
fi

# Test invalid workspace returns 404
INVALID_WS=$(curl -s -w "%{http_code}" -o /dev/null "$BASE_URL/org-a/invalid-ws/dashboard" || echo "000")
if [[ "$INVALID_WS" == "404" ]]; then
    pass "Invalid workspace returns 404"
else
    warn "Invalid workspace returned HTTP $INVALID_WS (expected 404)"
fi

# 10. Billing Webhooks
echo ""
echo "=== Billing Webhooks ==="
STRIPE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/webhooks/stripe" \
    -H "Content-Type: application/json" \
    -d '{"type": "ping"}' || echo "failed")

if echo "$STRIPE_RESPONSE" | grep -q "received"; then
    pass "Stripe webhook endpoint responding"
else
    warn "Stripe webhook test failed: $STRIPE_RESPONSE"
fi

PADDLE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/webhooks/paddle" \
    -H "Content-Type: application/json" \
    -d '{"event_type": "ping"}' || echo "failed")

if echo "$PADDLE_RESPONSE" | grep -q "received"; then
    pass "Paddle webhook endpoint responding"
else
    warn "Paddle webhook test failed: $PADDLE_RESPONSE"
fi

# 11. Notification Endpoint
echo ""
echo "=== Notifications ==="
if [ -n "$TOKEN" ]; then
    NOTIF_RESPONSE=$(curl -s -X POST "$BASE_URL/api/notifications/test" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $TOKEN" \
        -d '{"channel": "email", "to": "test@example.com", "subject": "Test", "body": "Test"}' || echo "failed")
    
    if echo "$NOTIF_RESPONSE" | grep -q "queued\|sent\|success"; then
        pass "Notification endpoint responding"
    else
        warn "Notification test failed: $NOTIF_RESPONSE"
    fi
else
    warn "Skipping notification test (no token provided)"
fi

# 12. API Endpoints
echo ""
echo "=== API Endpoints ==="
# Test API health
API_HEALTH=$(curl -s -w "%{http_code}" -o /dev/null "$BASE_URL/api/health" || echo "000")
if [[ "$API_HEALTH" == "200" ]]; then
    pass "API health endpoint"
else
    warn "API health returned HTTP $API_HEALTH"
fi

# 13. Static Assets
echo ""
echo "=== Static Assets ==="
ASSET_RESPONSE=$(curl -s -w "%{http_code}" -o /dev/null "$BASE_URL/build/assets/app.css" || echo "000")
if [[ "$ASSET_RESPONSE" == "200" ]]; then
    pass "CSS assets serving"
else
    warn "CSS assets returned HTTP $ASSET_RESPONSE"
fi

JS_RESPONSE=$(curl -s -w "%{http_code}" -o /dev/null "$BASE_URL/build/assets/app.js" || echo "000")
if [[ "$JS_RESPONSE" == "200" ]]; then
    pass "JS assets serving"
else
    warn "JS assets returned HTTP $JS_RESPONSE"
fi

# 14. Security Headers
echo ""
echo "=== Security Headers ==="
HEADERS=$(curl -s -I "$BASE_URL/" | tr -d '\r')
if echo "$HEADERS" | grep -qi "x-frame-options"; then
    pass "X-Frame-Options header present"
else
    warn "X-Frame-Options header missing"
fi

if echo "$HEADERS" | grep -qi "x-content-type-options"; then
    pass "X-Content-Type-Options header present"
else
    warn "X-Content-Type-Options header missing"
fi

if echo "$HEADERS" | grep -qi "strict-transport-security"; then
    pass "HSTS header present"
else
    warn "HSTS header missing"
fi

# 15. Rate Limiting
echo ""
echo "=== Rate Limiting ==="
for i in {1..5}; do
    RL_RESPONSE=$(curl -s -w "%{http_code}" -o /dev/null "$BASE_URL/api/health" || echo "000")
    if [[ "$RL_RESPONSE" == "429" ]]; then
        pass "Rate limiting active"
        break
    fi
    sleep 0.1
done

# Summary
echo ""
echo "=== Summary ==="
if [ $EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}All critical smoke tests passed!${NC}"
else
    echo -e "${RED}Some smoke tests failed!${NC}"
fi

exit $EXIT_CODE