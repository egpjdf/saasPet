# Compliance Rules - Saaspet (LGPD/GDPR)

> **Referência para Compliance Officer.** Regras não-negociáveis de conformidade legal.

---

## ⚖️ Marco Legal

| Regulamento | Jurisdição | Aplicabilidade |
|-------------|------------|----------------|
| **LGPD** (Lei 13.709/2018) | Brasil | **Obrigatório** - Dados de brasileiros |
| **GDPR** (Reg. 2016/679) | EU/EEE | **Obrigatório** - Dados de europeus (via Paddle MoR) |
| **Marco Civil da Internet** (Lei 12.965/2014) | Brasil | Logs de acesso, neutralidade |
| **Código de Defesa do Consumidor** (Lei 8.078/1990) | Brasil | Contratos, cancelamento, reembolso |

---

## 🏢 Papéis LGPD no Saaspet

| Ator | Papel LGPD | Responsabilidade |
|------|------------|------------------|
| **Saaspet (Platform)** | **Operador** | Processa dados em nome das Organizations; implementa medidas técnicas/organizacionais |
| **Organization** | **Controlador** | Define finalidade/tratamento dos dados de seus Workspaces/Users; responde perante titulares |
| **Workspace User** | **Titular** | Sujeito dos direitos (acesso, retificação, exclusão, portabilidade, oposição) |

### DPA (Data Processing Agreement)
- **Obrigatório** entre Saaspet (Operador) e cada Organization (Controlador)
- Assinado automaticamente na criação da Organization
- Versão controlada, renovável a cada 2 anos
- Cláusulas: finalidade, segurança, subprocessadores, transferência internacional, notificação de incidente, direitos do titular

---

## 👤 Direitos do Titular (LGPD Art. 18 / GDPR Art. 15-22)

### 1. Confirmação e Acesso (Art. 18, I / GDPR Art. 15)
```php
// Endpoint: GET /api/compliance/data-export
// Response: JSON + PDF com TODOS os dados do titular

$data = [
    'profile' => [name, email, phone, locale, timezone, created_at],
    'organization' => [id, name, slug],
    'workspace' => [id, name, slug, role],
    'consents' => [purpose, granted, granted_at, revoked_at, version, ip, ua],
    'billing' => [subscription, invoices, payment_methods],
    'activity' => [audit_logs (anonymized), login_history],
    'ai_data' => [conversations, embeddings, generated_content],
    'notifications' => [history, preferences],
    'integrations' => [connected_accounts, webhooks],
    'files' => [uploads, generated_documents],
];
```

### 2. Retificação (Art. 18, III / GDPR Art. 16)
```php
// PATCH /api/compliance/data-rectification
// Campos permitidos: name, phone, locale, timezone
// Log: old vs new values + timestamp + ip
```

### 3. Eliminação / Direito ao Esquecimento (Art. 18, VI / GDPR Art. 17)
```php
// DELETE /api/compliance/data-deletion
// Processo em 2 fases:
// 1. Soft delete + anonimização (reversível 30 dias)
// 2. Hard delete após grace period (irreversível)

// Exceções (mantidos por obrigação legal - Art. 16 LGPD):
// - Audit logs (segurança/fiscal) → ANONIMIZADOS
// - Billing records (fiscal 10 anos) → ANONIMIZADOS
// - Security events (5 anos) → ANONIMIZADOS
```

### 4. Portabilidade (Art. 18, V / GDPR Art. 20)
```php
// Mesmo endpoint de export, formato JSON estruturado (machine-readable)
// Inclui: profile, preferences, generated_content, connections
// Formato: JSON + CSV opcional
```

### 5. Oposição / Restrição (Art. 18, II, IV / GDPR Art. 18, 21)
```php
// Direito de opor-se a:
// - Marketing direto (sempre respeitado imediato)
// - Decisões automatizadas (AI profiling)
// - Processamento baseado em legítimo interesse

// Implementação: Consent revocation + processing stop
```

### 6. Não Discriminação (Art. 18, VII)
- Exercício de direitos **NÃO** pode resultar em degradação de serviço
- Features essenciais mantidas mesmo sem consentimentos opcionais

---

## 📝 Consent Management (LGPD Art. 7, 8 / GDPR Art. 7)

### Princípios
- **Livre:** Sem coerção, opt-in explícito
- **Informado:** Finalidade clara, linguagem simples
- **Específico:** Por finalidade (não "bundle")
- **Granular:** Canais + categorias separadas
- **Revogável:** Facilidade igual à concessão
- **Comprovado:** Log imutável (quem, quando, como, versão)

### Categorias de Consentimento
```php
// config/consent.php
return [
    'categories' => [
        'essential' => [
            'label' => 'Essenciais',
            'description' => 'Necessários para funcionamento do serviço',
            'required' => true, // Não pode revogar
            'purposes' => ['terms', 'privacy', 'security', 'billing', 'cookies_essential'],
        ],
        'analytics' => [
            'label' => 'Analytics',
            'description' => 'Medição de uso, performance, melhorias',
            'required' => false,
            'purposes' => ['analytics', 'cookies_analytics'],
            'third_parties' => ['Google Analytics', 'Mixpanel'],
        ],
        'marketing' => [
            'label' => 'Marketing',
            'description' => 'Novidades, ofertas, comunicações promocionais',
            'required' => false,
            'purposes' => ['marketing', 'cookies_marketing'],
            'third_parties' => ['Meta Pixel', 'Google Ads'],
        ],
        'ai_processing' => [
            'label' => 'Processamento por IA',
            'description' => 'Análise de dados para features de IA, embeddings',
            'required' => false,
            'purposes' => ['ai_processing'],
            'note' => 'Dados anonimizados quando possível',
        ],
        'third_party_sharing' => [
            'label' => 'Compartilhamento com Terceiros',
            'description' => 'Integrações ativas (CRM, WhatsApp, ERP, etc.)',
            'required' => false,
            'purposes' => ['third_party_sharing'],
        ],
    ],
    'versions' => [
        'terms' => '2024.1',
        'privacy' => '2024.1',
        'marketing' => '2024.1',
        'analytics' => '2024.1',
        'ai_processing' => '2024.1',
        'third_party_sharing' => '2024.1',
        'cookies_essential' => '2024.1',
        'cookies_analytics' => '2024.1',
        'cookies_marketing' => '2024.1',
    ],
];
```

### Consent Log (Imutável)
```php
// App\Models\ConsentLog.php
class ConsentLog extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, UsesUuids;
    
    protected $fillable = [
        'user_id', 'purpose', 'granted', 'granted_at', 'revoked_at',
        'version', 'ip_address', 'user_agent', 'metadata',
    ];
    
    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];
}
```

### Cookie Banner (Frontend)
```typescript
// Categorias: essential (required), analytics, marketing
// Banner aparece no primeiro acesso
// Salva consent em cookie + sync com backend se logado
// Link "Preferências" sempre acessível no footer
```

---

## 📅 Data Retention Policies

```php
// config/retention.php
return [
    'policies' => [
        // Dados operacionais - enquanto conta ativa
        'user_profile' => ['trigger' => 'account_deletion', 'grace_period_days' => 30],
        'workspace_data' => ['trigger' => 'workspace_deletion', 'grace_period_days' => 90],
        'organization_data' => ['trigger' => 'organization_deletion', 'grace_period_days' => 180],
        
        // Logs de auditoria - exigência legal/fiscal
        'audit_logs' => ['trigger' => 'fixed', 'retention_years' => 7],      // LGPD + Fiscal
        'security_events' => ['trigger' => 'fixed', 'retention_years' => 5],  // Segurança
        'billing_audit_logs' => ['trigger' => 'fixed', 'retention_years' => 10], // Fiscal Brasil
        
        // Consentimento
        'consent_logs' => ['trigger' => 'fixed', 'retention_years' => 10],    // Comprovação
        
        // Notificações
        'notifications' => ['trigger' => 'user_deletion', 'grace_period_days' => 30],
        'notification_preferences' => ['trigger' => 'user_deletion', 'grace_period_days' => 30],
        
        // AI/Embeddings
        'ai_embeddings' => ['trigger' => 'user_deletion', 'grace_period_days' => 30],
        'ai_conversations' => ['trigger' => 'user_deletion', 'grace_period_days' => 90],
        
        // Backups
        'database_backups' => ['trigger' => 'fixed', 'retention_days' => 90],
        'file_storage' => ['trigger' => 'user_deletion', 'grace_period_days' => 30],
    ],
];

// Job: EnforceRetentionPolicies (daily)
// - Fixed retention: DELETE WHERE created_at < NOW() - INTERVAL
// - Trigger-based: Processado no workflow de deletion
```

---

## 🌍 Transferência Internacional de Dados

| Destino | Finalidade | Base Legal | Salvaguardas |
|---------|------------|------------|--------------|
| **Stripe (EUA)** | Pagamentos | Execução de contrato | SCCs + Stripe DPF certified |
| **Paddle (UK)** | Pagamentos EU/UK | Execução de contrato | UK Adequacy Decision + SCCs |
| **Resend (EUA)** | Email transacional | Legítimo interesse / Contrato | SCCs + Resend DPA |
| **Firebase (EUA)** | Push notifications | Consentimento (opcional) | SCCs + Google DPF certified |
| **Cloudflare R2 (Global)** | File storage | Execução de contrato | SCCs + Cloudflare DPA |
| **Sentry (EUA)** | Error tracking | Legítimo interesse | SCCs + Sentry DPA |
| **OpenAI/Anthropic (EUA)** | AI Processing | Consentimento (ai_processing) | SCCs + DPAs |

### Mecanismos
- **SCCs (Standard Contractual Clauses)** 2021/914 para todos subprocessadores
- **DPF (Data Privacy Framework)** onde aplicável (EUA)
- **Adequacy Decisions** (UK, Canadá, Japão, etc.)
- **DPA assinado** com cada subprocessador

---

## 🚨 Data Breach Notification (LGPD Art. 48 / GDPR Art. 33, 34)

### Critérios de Notificação
| Risco | Ação | Prazo |
|-------|------|-------|
| **Baixo** | Log interno, mitigação | - |
| **Médio** | Log + notificar Organization (Controlador) | 24h |
| **Alto** | Notificar ANPD + Organization + Titulares afetados | 2 dias úteis (LGPD) / 72h (GDPR) |
| **Crítico** | Acima + imprensa/autoridades setoriais | Imediato |

### Processo
```php
// App\Services\Compliance\BreachNotificationService.php
class BreachNotificationService
{
    public function handleBreach(array $details): void
    {
        $breach = DataBreach::create([
            'organization_id' => $details['organization_id'],
            'detected_at' => now(),
            'description' => $details['description'],
            'affected_categories' => $details['categories'], // ['personal', 'financial', 'health', 'biometric']
            'estimated_affected_users' => $details['count'],
            'risk_level' => $this->assessRisk($details),
            'status' => 'investigating',
            'containment_actions' => $details['containment'] ?? [],
        ]);
        
        if (in_array($breach->risk_level, ['high', 'critical'])) {
            // 1. Notificar ANPD (LGPD) / Autoridade Supervisora (GDPR)
            $this->notifyAuthority($breach);
            
            // 2. Notificar Organization (Controlador)
            $this->notifyController($breach);
            
            // 3. Notificar Titulares (se alto risco aos direitos)
            if ($this->requiresSubjectNotification($breach)) {
                $this->notifySubjects($breach);
            }
        }
        
        // 4. Alertar Security Team + CTO + CO
        $this->alertInternal($breach);
    }
    
    private function assessRisk(array $details): string
    {
        $score = 0;
        $score += in_array('sensitive_personal_data', $details['categories'] ?? []) ? 30 : 0; // Art. 11 LGPD
        $score += in_array('financial_data', $details['categories'] ?? []) ? 25 : 0;
        $score += in_array('auth_credentials', $details['categories'] ?? []) ? 25 : 0;
        $score += ($details['count'] ?? 0) > 1000 ? 20 : 0;
        $score += ($details['count'] ?? 0) > 10000 ? 30 : 0;
        
        return match (true) {
            $score >= 70 => 'critical',
            $score >= 50 => 'high',
            $score >= 30 => 'medium',
            default => 'low',
        };
    }
}
```

---

## 📊 DPIA (Data Protection Impact Assessment)

### Quando Obrigatório (LGPD Art. 38 / GDPR Art. 35)
- Processamento em larga escala de dados sensíveis (Art. 11 LGPD)
- Monitoramento sistemático em larga escala
- Decisões automatizadas com efeitos jurídicos (AI scoring, credit)
- Novas tecnologias (AI/ML, biometria)
- Transferência internacional em larga escala

### Checklist DPIA
```php
// docs/compliance/dpia-checklist.md
class DpiaChecklist
{
    public static function required(array $feature): bool
    {
        return $feature['systematic_monitoring'] ?? false
            || $feature['large_scale_sensitive_data'] ?? false
            || $feature['automated_decision_making'] ?? false
            || $feature['innovative_technology'] ?? false // AI/ML
            || $feature['cross_border_transfer'] ?? false
            || $feature['vulnerable_subjects'] ?? false; // crianças, idosos
    }
    
    public static function generateReport(array $feature): array
    {
        return [
            'feature' => $feature['name'],
            'description' => $feature['description'],
            'data_categories' => $feature['data_categories'],
            'legal_basis' => $feature['legal_basis'], // consent, contract, legitimate_interest, vital_interest
            'necessity_proportionality' => $feature['necessity'],
            'risks' => $feature['risks'],
            'mitigation_measures' => $feature['mitigations'],
            'residual_risk' => $feature['residual_risk'],
            'dpo_approval' => null,
            'approved_at' => null,
        ];
    }
}
```

---

## 👨‍💼 DPO (Data Protection Officer)

### Contato
- **Email:** dpo@saaspet.com
- **Responsável:** [Nome] - Certificado EXIN Privacy & Data Protection
- **Disponibilidade:** Business hours + on-call para incidentes críticos

### Responsabilidades
1. Monitorar conformidade LGPD/GDPR
2. Assessorar CTO/CO em DPIAs
3. Ponto de contato para ANPD/Autoridades/ Titulares
4. Treinamento contínuo do time
5. Relatório anual de conformidade

---

## 🧪 Testes de Compliance (Obrigatórios no CI)

```php
// tests/Feature/Compliance/
test('data export includes all categories', fn() => { ... });
test('data deletion anonymizes audit logs but keeps structure', fn() => { ... });
test('data deletion queues third-party deletion jobs', fn() => { ... });
test('consent granular: can grant analytics but deny marketing', fn() => { ... });
test('consent versioning: re-consent required on version change', fn() => { ... });
test('cookie banner respects essential/analytics/marketing categories', fn() => { ... });
test('retention job deletes expired data only', fn() => { ... });
test('DPA auto-generated on organization creation', fn() => { ... });
test('breach notification assesses risk correctly', fn() => { ... });
test('cross-border transfer only with valid SCCs/DPF', fn() => { ... });
test('no discrimination when exercising rights', fn() => { ... });
```

---

## 📋 Auditoria Externa (Anual)

| Item | Frequência | Responsável |
|------|------------|-------------|
| **LGPD/GDPR Compliance Audit** | Anual | DPO + External Auditor |
| **Penetration Test** | Trimestral | Security Auditor + External |
| **Subprocessor Review** | Semestral | Compliance Officer |
| **DPIA Review** | Por feature / Anual | DPO + CTO |
| **Retention Policy Audit** | Anual | Compliance Officer |
| **Consent Log Integrity** | Mensal (automatizado) | QA Engineer |

---

**Versão:** 1.0  
**ADR:** ADR-001, ADR-009, ADR-014  
**Owner:** Compliance Officer (DPO) + CTO  
**Validação:** Security Auditor + QA Engineer + Tenant Guardian (automático)