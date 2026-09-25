# Security Requirements - Saaspet

> **Referência para Security Auditor.** Requisitos não-negociáveis de segurança para o projeto.

---

## 🎯 Princípios Fundamentais

1. **Zero Trust** - Nunca confie no input do cliente; valide tudo no servidor
2. **Defense in Depth** - Múltiplas camadas: WAF, Middleware, Policies, RLS, Crypto
3. **Least Privilege** - Acesso mínimo necessário por role/tenant
4. **Audit Everything** - Log imutável de todas ações sensíveis
5. **Secrets Zero** - Zero secrets no código; 1Password/Vault apenas
6. **Compliance by Design** - LGPD/GDPR nativo, não afterthought

---

## 🔐 Autenticação & Autorização

### Laravel Sanctum (SPA) + Fortify
```php
// config/sanctum.php
'expiration' => 15, // minutes para token de API
'token_prefix' => 'saaspet_',

// config/fortify.php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirms_password' => true,
    ]),
],
'passwords' => 'users',
```

### Rate Limiting (Por Tenant + Endpoint)
```php
// App\Providers\RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    $orgId = $request->header('X-Organization-ID') ?? 'anonymous';
    $key = "api:{$orgId}:{$request->ip()}";
    
    return Limit::perMinute(60)->by($key);
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(10)->by($request->ip());
});

RateLimiter::for('sensitive', function (Request $request) {
    $orgId = $request->header('X-Organization-ID') ?? 'anonymous';
    return Limit::perMinute(5)->by("sensitive:{$orgId}:{$request->ip()}");
});

// Uso nas rotas
Route::middleware(['throttle:api'])->group(...);
Route::middleware(['throttle:auth'])->group('/login', '/register', '/password/*');
Route::middleware(['throttle:sensitive'])->group('/billing/*', '/settings/*', '/webhooks/*');
```

### Two-Factor Authentication (2FA)
- **Obrigatório** para Platform Admins e Organization Owners
- **Opcional** para Workspace Users (configurável por Organization)
- **Recovery codes** gerados e mostrados uma vez
- **TOTP** via Google Authenticator / Authy

---

## 🛡️ Multi-Tenancy Security (Ver Tenant Guardian)

### RLS (Row Level Security) - **Obrigatório**
- Policy `organization_isolation` em TODAS tabelas com `organization_id`
- Policy `workspace_isolation` em TODAS tabelas com `workspace_id`
- `FOR ALL` para tabelas de dados; `FOR SELECT` para lookup tables
- Platform Admin via role `platform_admin` + session var `app.is_platform_admin`

### Global Scopes - **Obrigatório**
- `BelongsToOrganization` trait em Models de organization
- `BelongsToWorkspace` trait em Models de workspace
- Scopes aplicam `where('organization_id', app('tenant')->organizationId())`

### Policies - **Obrigatório**
- `view/update/delete` verificam `organization_id` E `workspace_id`
- `#[Authorize]` attributes em controllers (Laravel 13)

### Tenant Context Propagation
- Jobs: `HasTenantContext` interface
- Events: Listeners chamam `app('tenant')->setOrganization()/setWorkspace()`
- Cache: Keys prefixadas `tenant:{org_id}:workspace:{ws_id}:`
- Queue: `Queue::route()` por job class

---

## 🔑 Secrets Management

### Proibido
```php
// ❌ NUNCA
'stripe_secret' => 'sk_live_...',
'aws_secret' => 'AKIA...',
'jwt_secret' => 'hardcoded-secret',
```

### Obrigatório
```php
// config/services.php
'stripe' => [
    'secret' => env('STRIPE_SECRET'), // Apenas em .env / Vault
],

// .env (development apenas)
STRIPE_SECRET="sk_test_..."

// Produção: 1Password CLI / HashiCorp Vault
// GitHub Actions: OIDC + Vault secrets
```

### Secret Scanning (CI)
```yaml
# .github/workflows/security-scan.yml
- name: TruffleHog Secret Scan
  uses: trufflesecurity/trufflehog@v3
  with:
    path: ./
    base: ${{ github.event.pull_request.base.sha }}
    head: ${{ github.event.pull_request.head.sha }}
    fail: true
```

---

## 🌐 Web Security Headers

```php
// App\Http\Middleware\SecurityHeaders.php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // CSP - Restritivo mas funcional para Inertia/Vue
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com https://cdn.tailwindcss.com; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "font-src 'self' data: https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' https://api.stripe.com https://*.paddle.com wss://{$request->getHost()}; " .
               "frame-src https://js.stripe.com https://hooks.stripe.com; " .
               "base-uri 'self'; " .
               "form-action 'self';";
        
        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        
        // HSTS (apenas HTTPS em produção)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
        
        return $response;
    }
}
```

---

## 🔒 CSRF Protection (Laravel 13)

```php
// Middleware PreventRequestForgery (nativo no Laravel 13)
// Origin-aware verification + token-based fallback

// Config para SPA (Inertia)
'sanctum' => [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1,saaspet.com')),
    'expiration' => 15,
    'token_prefix' => 'saaspet_',
],
```

---

## 📝 Audit Logging (Imutável)

### Eventos Obrigatórios
```php
// App\Services\Security\AuditService.php
class AuditService
{
    public const ACTIONS = [
        // Auth
        'login', 'logout', 'login_failed', 'password_changed', 
        '2fa_enabled', '2fa_disabled', '2fa_recovery_used',
        'impersonation_started', 'impersonation_ended',
        
        // Tenant
        'organization_created', 'organization_updated', 'organization_suspended',
        'workspace_created', 'workspace_updated', 'workspace_deleted',
        'user_invited', 'user_joined', 'user_removed', 'user_role_changed',
        
        // Billing
        'subscription_created', 'subscription_updated', 'subscription_cancelled',
        'invoice_created', 'invoice_paid', 'invoice_refunded',
        'payment_method_added', 'payment_method_removed',
        
        // Data
        'data_export_requested', 'data_export_completed', 'data_download',
        'data_deletion_requested', 'data_deletion_completed',
        'data_rectified', 'consent_granted', 'consent_revoked',
        
        // Security
        'permission_changed', 'api_token_created', 'api_token_revoked',
        'webhook_endpoint_created', 'webhook_endpoint_updated',
        'webhook_delivery_failed', 'webhook_signature_invalid',
        
        // AI
        'ai_agent_invoked', 'ai_embedding_generated', 'ai_data_exported',
        
        // System
        'feature_flag_changed', 'maintenance_mode_toggled',
    ];
}
```

### Audit Log Model (Append-Only via Trigger PG)
```php
// App\Models\AuditLog.php
class AuditLog extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, UsesUuids;
    
    protected $fillable = [
        'user_id', 'action', 'details', 'ip_address', 'user_agent',
        'resource_type', 'resource_id', 'severity', 'metadata',
    ];
    
    protected $casts = [
        'details' => 'array',
        'metadata' => 'array',
        'severity' => 'string', // info, warning, critical
    ];
}
```

---

## 🔐 Encryption & Hashing

### Dados Sensíveis em Repouso
```php
// App\Models\Concerns\EncryptsSensitiveData.php
trait EncryptsSensitiveData
{
    protected static function booted(): void
    {
        static::saving(function ($model) {
            foreach ($model->getEncryptableAttributes() as $attribute) {
                if (!empty($model->{$attribute})) {
                    $model->{$attribute} = Crypt::encryptString($model->{$attribute});
                }
            }
        });
        
        static::retrieved(function ($model) {
            foreach ($model->getEncryptableAttributes() as $attribute) {
                if (!empty($model->{$attribute})) {
                    try {
                        $model->{$attribute} = Crypt::decryptString($model->{$attribute});
                    } catch (DecryptException $e) {
                        // Log corruption, manter criptografado
                        Log::error('Decryption failed', ['model' => get_class($model), 'attr' => $attribute]);
                    }
                }
            }
        });
    }
    
    abstract protected function getEncryptableAttributes(): array;
}

// Uso
class PaymentMethod extends Model
{
    use EncryptsSensitiveData;
    
    protected function getEncryptableAttributes(): array
    {
        return ['card_last4', 'card_brand', 'bank_account_last4', 'metadata'];
    }
}
```

### Password Hashing
```php
// config/hashing.php
'driver' => 'bcrypt',
'bcrypt' => [
    'rounds' => 12, // Mínimo 12 em 2024
],
'argon' => [
    'memory' => 65536,
    'threads' => 4,
    'time' => 4,
],
```

---

## 🛡️ Input Validation & Sanitization

### XSS Prevention
```php
// Blade: SEMPRE escape automático {{ $var }}
// NUNCA: {!! $var !!} ou @verbatim sem sanitização

// Markdown/HTML user-generated → Sanitize
use HtmlPurifier;

public function sanitizeHtml(string $html): string
{
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 'p,b,i,em,strong,a[href|title],ul,ol,li,br,span[style]');
    $config->set('URI.AllowedSchemes', ['http', 'https', 'mailto', 'tel']);
    $config->set('Attr.EnableID', true);
    
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($html);
}
```

### SQL Injection Prevention
- **SEMPRE** Eloquent/Query Builder (prepared statements)
- **NUNCA** `DB::raw()` com input do usuário não validado
- **SEMPRE** validation rules em Form Requests

---

## 🚨 Vulnerability Management

### Dependency Scanning
```yaml
# .github/workflows/security-scan.yml
- name: Composer Audit
  run: composer audit --no-dev --format=json --audit-level=high

- name: NPM Audit
  run: npm audit --audit-level=high --json

- name: Dependency Review
  uses: actions/dependency-review-action@v4
```

### Patch Policy
| Severidade | SLA Patch |
|------------|-----------|
| **Critical** (RCE, Auth Bypass) | 24 horas |
| **High** (SQLi, XSS, IDOR) | 72 horas |
| **Medium** (Info Disclosure, CSRF) | 7 dias |
| **Low** (Best Practice) | Próximo sprint |

---

## 🔍 Penetration Testing Checklist (Trimestral)

| Área | Testes |
|------|--------|
| **Auth** | Brute force, session fixation, token replay, 2FA bypass |
| **Authorization** | IDOR, privilege escalation, cross-tenant access, policy bypass |
| **Input** | XSS (stored/reflected/DOM), SQLi, NoSQLi, command injection |
| **API** | Rate limit bypass, mass assignment, broken object auth |
| **Webhooks** | Signature bypass, replay attacks, SSRF via webhook URL |
| **Files** | Upload bypass, path traversal, mime confusion |
| **Business Logic** | Price manipulation, coupon abuse, trial bypass |
| **Multi-Tenant** | Cross-org data leak, cross-ws data leak, RLS bypass |

---

## 📋 Security Gates (Definition of Done)

| Gate | Ferramenta | Threshold | Bloqueia Merge |
|------|------------|-----------|----------------|
| **Secret Scan** | TruffleHog | 0 findings | ✅ Sim |
| **Dependency Audit** | Composer/NPM Audit | 0 critical/high | ✅ Sim |
| **Static Analysis** | PHPStan + Security Rules | 0 errors | ✅ Sim |
| **Security Audit** | Security Auditor Agent | 0 critical/high findings | ✅ Sim |
| **Tenant Isolation** | Tenant Guardian + QA Tests | 0 leaks | ✅ Sim |
| **Pen Test** | Manual (trimestral) | 0 critical/high | ⚠️ Release Gate |

---

## 📚 Referências

- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [OWASP ASVS 4.0](https://owasp.org/www-project-application-security-verification-standard/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [LGPD (Lei 13.709/2018)](https://www.gov.br/anpd/pt-br/assuntos/lgpd)
- [GDPR Art. 25/32](https://gdpr.eu/article-25-data-protection-by-design-and-by-default/)

---

**Versão:** 1.0  
**ADR:** ADR-001, ADR-004, ADR-010  
**Owner:** CTO + Security Auditor  
**Validação:** Security Auditor Agent (automático em todo PR)