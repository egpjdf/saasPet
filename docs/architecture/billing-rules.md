# Billing Rules - Saaspet

> **Regras de negócio para Billing Engineer.** Cashier Stripe + Paddle, multi-tenant, compliance fiscal.

---

## 🎯 Visão Geral

**Modelo:** Organization = Billable Entity (Cashier `Billable` trait)
**Providers:** Stripe (Global) + Paddle (EU/UK - MoR para VAT)
**Moedas:** BRL (primária), USD, EUR
**Ciclo:** Monthly/Yearly com trial 14 dias

---

## 📦 Planos & Limites

```php
// config/billing.php
return [
    'plans' => [
        'free' => [
            'name' => 'Free',
            'description' => 'Para começar',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'stripe_price_id' => null,
            'paddle_price_id' => null,
            'trial_days' => 0,
            'features' => ['basic_pos', 'basic_reports'],
            'limits' => [
                'workspaces' => 1,
                'users_per_workspace' => 3,
                'api_calls_monthly' => 1_000,
                'ai_tokens_monthly' => 10_000,
                'storage_mb' => 100,
                'webhooks_out' => 0,
                'integrations' => 0,
            ],
        ],
        'starter' => [
            'name' => 'Starter',
            'price_monthly' => 99_00, // centavos BRL
            'price_yearly' => 990_00,
            'stripe_price_id' => env('STRIPE_STARTER_MONTHLY'),
            'paddle_price_id' => env('PADDLE_STARTER_MONTHLY'),
            'trial_days' => 14,
            'features' => ['pos', 'reports', 'api_access', 'email_notifications'],
            'limits' => [
                'workspaces' => 3,
                'users_per_workspace' => 10,
                'api_calls_monthly' => 100_000,
                'ai_tokens_monthly' => 1_000_000,
                'storage_mb' => 5_000,
                'webhooks_out' => 5,
                'integrations' => 3,
            ],
        ],
        'professional' => [
            'name' => 'Professional',
            'price_monthly' => 299_00,
            'price_yearly' => 2_990_00,
            'stripe_price_id' => env('STRIPE_PRO_MONTHLY'),
            'paddle_price_id' => env('PADDLE_PRO_MONTHLY'),
            'trial_days' => 14,
            'features' => ['pos', 'reports', 'api_access', 'sms_notifications', 'webhooks', 'integrations', 'ai_assistant'],
            'limits' => [
                'workspaces' => 10,
                'users_per_workspace' => 50,
                'api_calls_monthly' => 1_000_000,
                'ai_tokens_monthly' => 10_000_000,
                'storage_mb' => 50_000,
                'webhooks_out' => 20,
                'integrations' => 10,
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_monthly' => 999_00,
            'price_yearly' => 9_990_00,
            'stripe_price_id' => env('STRIPE_ENT_MONTHLY'),
            'paddle_price_id' => env('PADDLE_ENT_MONTHLY'),
            'trial_days' => 30,
            'features' => ['all', 'custom_integrations', 'sso', 'dedicated_support', 'sla'],
            'limits' => [
                'workspaces' => -1, // unlimited
                'users_per_workspace' => -1,
                'api_calls_monthly' => -1,
                'ai_tokens_monthly' => -1,
                'storage_mb' => -1,
                'webhooks_out' => -1,
                'integrations' => -1,
            ],
        ],
    ],
    
    'overage' => [
        'api_call_price' => 0.001, // BRL por call excedente
        'ai_token_price' => 0.00001, // BRL por 1k tokens
        'storage_mb_price' => 0.10, // BRL por MB
    ],
    
    'tax' => [
        'enabled' => true,
        'provider' => 'paddle', // Paddle = Merchant of Record para EU
        'fallback_provider' => 'stripe_tax',
        'brazil' => [
            'regime' => 'simples_nacional', // Padrão para PMEs
            'municipality_codes' => [], // Para ISS
        ],
    ],
];
```

---

## 💳 Subscription Lifecycle

### Estados da Subscription
```
trialing → active → past_due → canceled
              ↓
           paused (manual)
              ↓
           active (resume)
```

### Trial (14 dias padrão)
- **Início:** No checkout (Stripe/Paddle)
- **Fim:** Transição automática para `active` + cobrança
- **Extensão:** Apenas via Platform Admin ou Support (audit log)
- **Cancelamento durante trial:** Imediato, sem cobrança

### Upgrade/Downgrade
```php
// Regras de Proration
Upgrade (ex: Starter → Pro):
  - Imediato: Proration charge (crédito dias restantes + débito novo plano)
  - Invoice gerada imediatamente
  - Limites novos aplicados imediatamente

Downgrade (ex: Pro → Starter):
  - No final do período: `swap()` sem proration (padrão)
  - Ou imediato com crédito: `swapAndInvoice()` → crédito pro-rata
  - Limites novos aplicados no próximo ciclo

Cross-provider (Stripe ↔ Paddle):
  - Cancel current → Create new no novo provider
  - Credit note no provider antigo
  - Requer validação de elegibilidade (país, moeda)
```

### Cancellation
```php
// Cancelamento no final do período (padrão)
$subscription->cancel(); // ends_at = current_period_end

// Cancelamento imediato + reembolso (se elegível)
$subscription->cancelNow(); // ends_at = now

// Reactivação (se não expired)
$subscription->resume(); // Remove ends_at, reativa
```

### Dunning (Recuperação de Pagamento)
```php
// Stripe: Configurado no Dashboard
// 1. Tentativa 1: Imediata (falha)
// 2. Tentativa 2: 3 dias depois
// 3. Tentativa 3: 7 dias depois
// 4. Após 3 falhas: subscription → past_due → canceled (configurável)

// Webhooks para notificar
invoice.payment_failed → NotificationEngineer → Email + In-app
customer.subscription.updated (past_due) → NotificationEngineer → Email + In-app
customer.subscription.deleted → NotificationEngineer → Email + In-app + Lock features
```

---

## 🧾 Invoices & Tax Compliance

### Invoice Generation
```php
// Automático via Cashier
// Custom line items para overages
$subscription->createInvoice([
    'description' => 'Uso excedente de API',
    'quantity' => $overageCalls,
    'unit_amount' => config('billing.overage.api_call_price') * 100, // centavos
]);
```

### Tax (LGPD + IVA EU)
```php
// Paddle: Merchant of Record → Coleta VAT automaticamente para EU
// Stripe Tax: Cálculo automático baseado em endereço do cliente

// Brasil: Simples Nacional (padrão) ou Lucro Presumido/Real
// ISS (municipal) - responsabilidade da Organization
// NF-e: Integração futura com provedor fiscal (NFe.io, Focus, etc.)

// Validação CNPJ/CPF
public function validateTaxId(string $taxId): bool
{
    // Algoritmo CNPJ/CPF + consulta ReceitaWS (opcional)
}
```

### Invoice PDF
- Template Blade com branding da Organization
- QR Code PIX (Brasil) se BRL
- Dados fiscais completos (CNPJ, endereço, regime tributário)
- Download via `/api/billing/invoices/{invoice}/pdf`

---

## 💰 Usage-Based Billing (Metered)

### Métricas Medidas
| Métrica | Unidade | Plano Free | Starter | Pro | Enterprise |
|---------|---------|------------|---------|-----|------------|
| API Calls | calls/month | 1,000 | 100,000 | 1M | Unlimited |
| AI Tokens | tokens/month | 10k | 1M | 10M | Unlimited |
| Storage | MB | 100 | 5,000 | 50,000 | Unlimited |
| Webhooks Out | deliveries/month | 0 | 5 | 20 | Unlimited |

### Reporte de Uso (Job Agendado)
```php
// App\Jobs\ReportAggregatedUsage.php (Hourly)
class ReportAggregatedUsage implements ShouldQueue
{
    public function handle(): void
    {
        Organization::whereHas('subscription', fn($q) => $q->whereNotNull('stripe_id'))
            ->chunkById(100, function ($orgs) {
                foreach ($orgs as $org) {
                    $this->reportApiCalls($org);
                    $this->reportAiTokens($org);
                    $this->reportStorage($org);
                }
            });
    }
    
    private function reportApiCalls(Organization $org): void
    {
        $calls = ApiCallLog::where('organization_id', $org->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        
        if ($calls > 0) {
            $org->subscription('default')->reportUsage('api_calls', $calls);
        }
    }
}
```

---

## 🚫 Limits Enforcement (Runtime)

```php
// App\Services\Billing\LimitEnforcer.php
class LimitEnforcer
{
    public function enforce(Organization $org, string $limit): void
    {
        $plan = $org->currentPlan();
        $limitConfig = config("billing.plans.{$plan}.limits.{$limit}");
        
        if ($limitConfig === -1) return; // Unlimited
        
        $current = $this->getCurrentUsage($org, $limit);
        
        if ($current >= $limitConfig) {
            throw new LimitExceededException(
                "Limite de {$limit} excedido ({$current}/{$limitConfig}). " .
                "Faça upgrade em: " . route('billing.plans')
            );
        }
    }
    
    public function getCurrentUsage(Organization $org, string $limit): int
    {
        return match ($limit) {
            'workspaces' => $org->workspaces()->count(),
            'users_per_workspace' => $org->workspaces()
                ->withCount('users')
                ->get()
                ->max('users_count') ?? 0,
            'api_calls_monthly' => ApiCallLog::where('organization_id', $org->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'ai_tokens_monthly' => AiUsageLog::where('organization_id', $org->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('tokens'),
            'storage_mb' => (int) ($org->getTotalStorageBytes() / 1024 / 1024),
            default => 0,
        };
    }
}

// Uso em Controllers/Services
$limitEnforcer->enforce($org, 'api_calls_monthly');
$limitEnforcer->enforce($org, 'ai_tokens_monthly');
```

---

## 🔄 Webhooks (Stripe + Paddle)

### Stripe Events Processados
| Event | Ação |
|-------|------|
| `checkout.session.completed` | Criar/Atualizar subscription local |
| `invoice.payment_succeeded` | Marcar invoice paid, enviar recibo |
| `invoice.payment_failed` | Trigger dunning, notificar |
| `customer.subscription.updated` | Sync status, plan, quantity |
| `customer.subscription.deleted` | Cancel local, lock features |
| `customer.subscription.trial_will_end` | Notificar 3 dias antes |
| `payment_method.attached` | Sync payment method |
| `charge.refunded` | Criar credit note, notificar |

### Paddle Events Processados
| Event | Ação |
|-------|------|
| `subscription_created` | Criar subscription local |
| `subscription_updated` | Sync status, plan |
| `subscription_cancelled` | Cancel local |
| `transaction.completed` | Invoice paid |
| `transaction.failed` | Dunning |
| `transaction.refunded` | Credit note |

### Idempotency & Signature Verification
```php
// Obrigatório em TODOS webhooks
$event = Webhook::constructEvent($payload, $sigHeader, $secret);
// Processar via Job assíncrono (queue: webhooks)
ProcessStripeWebhook::dispatch($event)->onQueue('webhooks');
```

---

## 🌍 Multi-Currency & Localization

| País/Região | Moeda | Provider | Tax |
|-------------|-------|----------|-----|
| Brasil | BRL | Stripe | ICMS/IPI/ISS (Simples) |
| EUA | USD | Stripe | Sales Tax (Stripe Tax) |
| EU | EUR | Paddle | VAT (Paddle MoR) |
| UK | GBP | Paddle | VAT (Paddle MoR) |
| Resto | USD | Stripe | Stripe Tax |

### Localização de Preços
```php
// Preços armazenados em centavos (integer) na moeda base (BRL)
// Conversão em tempo real via API (ex: exchangerate.host) para display
// Checkout sempre na moeda do cliente (Stripe/Paddle handle)
```

---

## 🧪 Testes Obrigatórios

```php
// tests/Feature/Billing/
test('stripe checkout creates subscription', fn() => { ... });
test('paddle checkout creates subscription (EU)', fn() => { ... });
test('upgrade prorates correctly', fn() => { ... });
test('downgrade at period end no proration', fn() => { ... });
test('trial ends and charges', fn() => { ... });
test('cancellation immediate vs end of period', fn() => { ... });
test('dunning flow after failed payment', fn() => { ... });
test('invoice generated with correct tax', fn() => { ... });
test('usage reporting aggregates correctly', fn() => { ... });
test('limit enforcement blocks overage', fn() => { ... });
test('limit enforcement allows within limit', fn() => { ... });
test('webhook signature verification rejects invalid', fn() => { ... });
test('idempotent webhook processing', fn() => { ... });
test('cross-provider switch (stripe->paddle)', fn() => { ... });
```

---

## 📋 Compliance Checklist

- [ ] **PCI DSS SAQ A** (Stripe/Paddle handle card data)
- [ ] **LGPD** - Data export/deletion para billing data
- [ ] **GDPR** - Right to portability, deletion, DPA com Stripe/Paddle
- [ ] **Tax** - VAT EU via Paddle MoR, Sales Tax US via Stripe Tax, ISS Brasil via Organization
- [ ] **Invoice Retention** - 10 anos (fiscal Brasil) / 7 anos (EU)
- [ ] **Audit Log** - Todas ações de billing logadas
- [ ] **Refund Policy** - Documentada, automatizada para elegíveis

---

**Versão:** 1.0  
**Owner:** Billing Engineer + CTO  
**Validação:** QA Engineer + Security Auditor + Compliance Officer