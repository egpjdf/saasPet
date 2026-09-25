# Notification Rules - Saaspet

> **Regras para Notification Engineer.** Multi-canal, tenant-aware, LGPD-compliant.

---

## 🎯 Visão Geral

**Canais:** Email (Resend), SMS (Twilio), Push (Firebase), In-App (Database), Real-time (Reverb), Webhook (Outbound)
**Isolamento:** TODAS notificações escopadas por `organization_id` + `workspace_id`
**LGPD:** Consentimento granular, opt-out, retention, right to deletion

---

## 📧 Email (Resend)

### Configuração
```php
// config/mail.php
'mailers' => [
    'resend' => [
        'transport' => 'resend',
        'key' => env('RESEND_API_KEY'),
    ],
],

// services.php
'resend' => [
    'key' => env('RESEND_API_KEY'),
],
```

### Domínios & Remetentes
| Tipo | De | Domínio |
|------|-----|---------|
| **Transacional** | `noreply@saaspet.com` | `saaspet.com` (verified) |
| **Marketing** | `marketing@saaspet.com` | `mail.saaspet.com` (subdomain) |
| **Security** | `security@saaspet.com` | `saaspet.com` |
| **Billing** | `billing@saaspet.com` | `saaspet.com` |

### Templates (Blade + Markdown + Inlined CSS)
```blade
<!-- resources/views/emails/layouts/default.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <style>
        /* Tailwind inlined via CSS inliner */
        @tailwind base; @tailwind components; @tailwind utilities;
        /* Fallback inline styles para clientes sem suporte */
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" class="w-full max-w-2xl">
                    <!-- Header -->
                    <tr>
                        <td class="p-6 bg-red-600 text-white">
                            <img src="{{ asset('logo-white.png') }}" alt="Saaspet" width="120">
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td class="p-6 bg-white dark:bg-gray-800">
                            {{ $slot }}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td class="p-6 bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 dark:text-gray-400 text-center">
                            <p>Você recebeu este email pois está cadastrado em <strong>{{ $organization->name }}</strong>.</p>
                            <p>
                                <a href="{{ $unsubscribeUrl }}" class="text-gray-500 underline">Cancelar inscrição</a> |
                                <a href="{{ $preferencesUrl }}" class="text-gray-500 underline">Preferências</a>
                            </p>
                            <p>&copy; {{ date('Y') }} Saaspet. Todos os direitos reservados.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
```

### Notificações Obrigatórias (Por Categoria)

#### Auth & Security (Sempre enviam - LGPD Art. 7 IX)
| Evento | Canal | Template | Prioridade |
|--------|-------|----------|------------|
| Welcome / Email Verification | Email + In-App | `emails.auth.verification` | High |
| Password Reset | Email | `emails.auth.password-reset` | Critical |
| 2FA Enabled/Disabled | Email + In-App | `emails.auth.2fa-changed` | High |
| New Device Login | Email + In-App | `emails.security.new-device` | High |
| Suspicious Activity | Email + In-App + SMS (se crítico) | `emails.security.suspicious` | Critical |
| Impersonation Started/Ended | Email (target) + In-App | `emails.security.impersonation` | High |

#### Billing (Sempre enviam - Contratual)
| Evento | Canal | Template | Prioridade |
|--------|-------|----------|------------|
| Trial Ending (3 dias) | Email + In-App | `emails.billing.trial-ending` | High |
| Invoice Created | Email + In-App | `emails.billing.invoice-created` | Normal |
| Invoice Paid | Email + In-App | `emails.billing.invoice-paid` | Normal |
| Invoice Failed | Email + In-App + SMS | `emails.billing.invoice-failed` | Critical |
| Subscription Canceled | Email + In-App | `emails.billing.canceled` | High |
| Payment Method Expiring | Email + In-App | `emails.billing.pm-expiring` | Normal |
| Refund Processed | Email + In-App | `emails.billing.refund` | Normal |

#### Operational (Respeitam Preferences)
| Evento | Canal | Template | Categoria Preference |
|--------|-------|----------|---------------------|
| New Order | In-App + Reverb + Push | `emails.operational.new-order` | `operational` |
| Order Status Change | In-App + Reverb | `emails.operational.status-change` | `operational` |
| Low Stock Alert | Email + In-App + Push | `emails.operational.low-stock` | `operational` |
| Appointment Reminder | Email + SMS + Push | `emails.operational.appointment-reminder` | `operational` |
| Team Invitation | Email + In-App | `emails.team.invitation` | `team` |
| Feature Announcement | Email (digest) + In-App | `emails.product.announcement` | `product` |

---

## 🔔 In-App + Real-time (Reverb)

### Database Notifications (Laravel Native)
```php
// Migration: notifications table (já existe)
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('type');
    $table->morphs('notifiable'); // user_id + notifiable_type
    $table->json('data');
    $table->timestamp('read_at')->nullable();
    
    // TENANT COLUMNS - OBRIGATÓRIAS
    $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
    
    $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
    $table->index(['organization_id', 'workspace_id', 'created_at']);
});
```

### Reverb Channel (Real-time)
```php
// App\Events\NotificationSent.php
class NotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, HasTenantContext;
    
    public function __construct(
        public int $userId,
        public string $organizationId,
        public string $workspaceId,
        public array $data
    ) {}
    
    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }
    
    public function broadcastWith(): array
    {
        return ['notification' => $this->data];
    }
}

// Canal privado exige auth
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

### Vue Composables (Frontend)
```typescript
// resources/js/Composables/useNotifications.ts
import { ref, onMounted } from 'vue'
import { useEcho } from '@/Composables/useEcho'
import { useToast } from '@/Composables/useToast'

export function useNotifications() {
    const notifications = ref<Notification[]>([])
    const unreadCount = ref(0)
    const echo = useEcho()
    
    onMounted(() => {
        if (!echo.value) return
        
        echo.value.private(`user.${userId}`)
            .listen('NotificationSent', (e: any) => {
                notifications.value.unshift(e.notification)
                unreadCount.value++
                
                // Toast não-intrusivo
                useToast().show(e.notification.title, e.notification.message, {
                    action: e.notification.action_url ? { label: 'Ver', url: e.notification.action_url } : null
                })
            })
    })
    
    const markAsRead = async (id: string) => {
        await api.post(`/notifications/${id}/read`)
        const n = notifications.value.find(n => n.id === id)
        if (n) { n.read_at = new Date().toISOString(); unreadCount.value-- }
    }
    
    const markAllAsRead = async () => {
        await api.post('/notifications/read-all')
        notifications.value.forEach(n => n.read_at = new Date().toISOString())
        unreadCount.value = 0
    }
    
    return { notifications, unreadCount, markAsRead, markAllAsRead }
}
```

---

## 📱 Push Notifications (Firebase FCM)

### Token Management
```php
// App\Models\FcmToken.php
class FcmToken extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, UsesUuids;
    
    protected $fillable = ['user_id', 'token', 'platform', 'device_info'];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

// Controller para registrar token (chamado do frontend)
public function registerFcmToken(Request $request): JsonResponse
{
    $request->validate([
        'token' => 'required|string',
        'platform' => 'required|in:ios,android,web',
        'device_info' => 'nullable|array',
    ]);
    
    FcmToken::updateOrCreate(
        ['user_id' => auth()->id(), 'token' => $request->token],
        ['platform' => $request->platform, 'device_info' => $request->device_info]
    );
    
    return response()->json(['success' => true]);
}
```

### Channel Implementation
```php
// App\Notifications\Channels\FirebasePushChannel.php
class FirebasePushChannel
{
    public function __construct(protected Messaging $firebase) {}
    
    public function send(object $notifiable, Notification $notification): void
    {
        $tokens = $notifiable->fcmTokens()->pluck('token')->toArray();
        if (empty($tokens)) return;
        
        $message = $notification->toPush($notifiable);
        
        $this->firebase->sendMulticast([
            'tokens' => $tokens,
            'notification' => [
                'title' => $message['title'],
                'body' => $message['body'],
                'image' => $message['image'] ?? null,
            ],
            'data' => $message['data'] ?? [],
            'android' => ['priority' => 'high', 'notification' => ['channel_id' => 'default']],
            'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
        ]);
    }
}
```

---

## 📲 SMS (Twilio) - Para Alertas Críticos

```php
// App\Notifications\Channels\TwilioSmsChannel.php
class TwilioSmsChannel
{
    public function __construct(
        protected Client $twilio,
        protected string $fromNumber
    ) {}
    
    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $notifiable->phone_number;
        if (!$phone) return;
        
        $this->twilio->messages->create($phone, [
            'from' => $this->fromNumber,
            'body' => $notification->toSms($notifiable),
        ]);
    }
}
```

**Apenas para:** Invoice failed, Security alerts, Appointment reminders (se opt-in)

---

## ⚙️ Preferences (LGPD Granular)

### Model
```php
// App\Models\NotificationPreference.php
class NotificationPreference extends Model
{
    use BelongsToOrganization, BelongsToWorkspace, UsesUuids;
    
    protected $fillable = [
        'user_id',
        // Canais
        'mail', 'sms', 'push', 'database', 'reverb',
        // Categorias
        'types',
        // Digest
        'digest_frequency',
    ];
    
    protected $casts = [
        'mail' => 'boolean',
        'sms' => 'boolean',
        'push' => 'boolean',
        'database' => 'boolean',
        'reverb' => 'boolean',
        'types' => 'array',
        'digest_frequency' => 'string', // immediate, daily, weekly, never
    ];
    
    // Defaults
    protected $attributes = [
        'mail' => true,
        'sms' => false,
        'push' => true,
        'database' => true,
        'reverb' => true,
        'types' => [
            'security' => true,      // Sempre true (não pode desligar)
            'billing' => true,       // Sempre true
            'operational' => true,
            'team' => true,
            'product' => false,      // Marketing/Feature announcements
        ],
        'digest_frequency' => 'immediate',
    ];
}
```

### BaseNotification (Respeita Preferences)
```php
// App\Notifications\BaseNotification.php
abstract class BaseNotification extends Notification
{
    use HasTenantContext;
    
    public function __construct(
        public readonly string $organizationId,
        public readonly string $workspaceId,
        public readonly array $data = [],
        public readonly string $type = 'operational' // security, billing, operational, team, product
    ) {}
    
    public function via(object $notifiable): array
    {
        $prefs = $notifiable->notificationPreferences()
            ->where('organization_id', $this->organizationId)
            ->where('workspace_id', $this->workspaceId)
            ->first();
        
        if (!$prefs) return $this->defaultChannels();
        
        $channels = $this->defaultChannels();
        
        // Sempre enviar security/billing independente de preferência
        if (in_array($this->type, ['security', 'billing'])) {
            return $channels;
        }
        
        // Filtrar por preferências de canal
        $channels = array_filter($channels, fn($c) => $prefs->{$c} ?? true);
        
        // Filtrar por categoria
        if (!$prefs->types[$this->type] ?? true) {
            return [];
        }
        
        return $channels;
    }
    
    abstract protected function defaultChannels(): array;
}
```

---

## 📦 Digest & Batching

### Digest Notification
```php
// App\Notifications\DigestNotification.php
class DigestNotification extends BaseNotification
{
    protected function defaultChannels(): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Resumo diário: {$this->data['count']} novas notificações")
            ->markdown('emails.digest', [
                'notifications' => $this->data['notifications'],
                'unsubscribeUrl' => $this->unsubscribeUrl($notifiable),
            ]);
    }
}
```

### Job Agendado
```php
// App\Jobs\SendDigestNotification.php
class SendDigestNotification implements ShouldQueue
{
    public function __construct(public string $frequency) {} // daily, weekly
    
    public function handle(): void
    {
        NotificationPreference::where('digest_frequency', $this->frequency)
            ->chunkById(100, function ($prefs) {
                foreach ($prefs as $pref) {
                    $notifications = DatabaseNotification::where('notifiable_id', $pref->user_id)
                        ->where('organization_id', $pref->organization_id)
                        ->where('workspace_id', $pref->workspace_id)
                        ->whereNull('read_at')
                        ->where('created_at', '>=', $this->getSince())
                        ->get();
                    
                    if ($notifications->isNotEmpty()) {
                        $pref->notifiable->notify(new DigestNotification(
                            $pref->organization_id,
                            $pref->workspace_id,
                            ['notifications' => $notifications, 'count' => $notifications->count()]
                        ));
                    }
                }
            });
    }
    
    private function getSince(): Carbon
    {
        return match ($this->frequency) {
            'daily' => now()->subDay(),
            'weekly' => now()->subWeek(),
            default => now()->subDay(),
        };
    }
}

// Schedule
$schedule->job(new SendDigestNotification('daily'))->dailyAt('08:00');
$schedule->job(new SendDigestNotification('weekly'))->weeklyOn(1, '08:00');
```

---

## 🔁 Retry Logic & Dead Letter Queue

```php
// BaseNotification
public $tries = 3;
public $backoff = [60, 300, 900]; // 1min, 5min, 15min
public $timeout = 60;

public function failed(\Throwable $exception): void
{
    FailedNotification::create([
        'notification_class' => get_class($this),
        'notifiable_type' => get_class($this->notifiable),
        'notifiable_id' => $this->notifiable->id,
        'organization_id' => $this->organizationId,
        'workspace_id' => $this->workspaceId,
        'type' => $this->type,
        'error' => $exception->getMessage(),
        'payload' => json_encode($this->data),
        'failed_at' => now(),
    ]);
    
    if ($this->type === 'security' || $this->type === 'billing') {
        $this->alertOnCall($exception); // PagerDuty/Opsgenie
    }
}
```

---

## 🚫 Unsubscribe & LGPD Compliance

### Signed Unsubscribe URLs
```php
// App\Services\Notification\UnsubscribeService.php
class UnsubscribeService
{
    public function generateUrl(User $user, Organization $org, Workspace $ws, string $type = 'all'): string
    {
        return URL::signedRoute('unsubscribe', [
            'user' => $user->id,
            'organization' => $org->id,
            'workspace' => $ws->id,
            'type' => $type,
        ]);
    }
    
    public function process(Request $request): void
    {
        $request->validate([
            'user' => 'required|uuid|exists:users,id',
            'organization' => 'required|uuid|exists:organizations,id',
            'workspace' => 'required|uuid|exists:workspaces,id',
            'type' => 'required|in:all,marketing,product,operational,team',
        ]);
        
        $prefs = NotificationPreference::where('user_id', $request->user)
            ->where('organization_id', $request->organization)
            ->where('workspace_id', $request->workspace)
            ->firstOrFail();
        
        if ($request->type === 'all') {
            $prefs->update([
                'mail' => false, 'sms' => false, 'push' => false,
                'database' => false, 'reverb' => false,
            ]);
        } else {
            $types = $prefs->types;
            $types[$request->type] = false;
            $prefs->update(['types' => $types]);
        }
        
        // Log para auditoria
        AuditLog::create([
            'organization_id' => $request->organization,
            'workspace_id' => $request->workspace,
            'user_id' => $request->user,
            'action' => 'unsubscribe',
            'details' => ['type' => $request->type],
        ]);
    }
}
```

### Data Deletion (LGPD Art. 18 VI)
```php
public function deleteUserNotifications(User $user, Organization $org, Workspace $ws): void
{
    // Anonimiza (mantém estrutura para integridade relacional)
    DatabaseNotification::where('notifiable_id', $user->id)
        ->where('organization_id', $org->id)
        ->where('workspace_id', $ws->id)
        ->update(['data' => ['anonymized' => true, 'deleted_at' => now()]]); 
    
    // Remove preferences
    NotificationPreference::where('user_id', $user->id)
        ->where('organization_id', $org->id)
        ->where('workspace_id', $ws->id)
        ->delete();
    
    // Remove FCM tokens
    $user->fcmTokens()->delete();
}
```

---

## 🧪 Testes Obrigatórios

```php
// tests/Feature/Notifications/
test('email renders correctly with tenant branding', fn() => { ... });
test('channel selection respects user preferences', fn() => { ... });
test('security/billing notifications ignore preferences', fn() => { ... });
test('reverb broadcast payload structure', fn() => { ... });
test('digest aggregates correctly', fn() => { ... });
test('retry/backoff works for failed deliveries', fn() => { ... });
test('unsubscribe flow updates preferences', fn() => { ... });
test('LGPD data deletion anonymizes notifications', fn() => { ... });
test('tenant isolation: org A notifications not visible to org B', fn() => { ... });
test('fcm token registration and cleanup', fn() => { ... });
```

---

**Versão:** 1.0  
**Owner:** Notification Engineer + CTO  
**Validação:** QA Engineer + Compliance Officer + Tenant Guardian