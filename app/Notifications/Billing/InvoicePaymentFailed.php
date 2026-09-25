<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Billing\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\Tenant\HasTenantContext;

class InvoicePaymentFailed extends Notification implements ShouldQueue
{
    use Queueable, HasTenantContext;

    public function __construct(
        public Subscription $subscription,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Falha no pagamento da sua assinatura')
            ->greeting('Olá ' . $notifiable->name . ',')
            ->line('Tivemos um problema ao processar o pagamento da sua assinatura.')
            ->line('Plano: ' . $this->subscription->plan?->name)
            ->line('Valor: ' . $this->subscription->plan?->priceFormatted())
            ->action('Atualizar forma de pagamento', $this->getBillingPortalUrl())
            ->line('Se você já atualizou seus dados de pagamento, tentaremos cobrar novamente em breve.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'invoice_payment_failed',
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan?->name,
            'amount' => $this->subscription->plan?->price_cents,
        ];
    }

    private function getBillingPortalUrl(): string
    {
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);
        
        if ($tenantContext->isPlatformAdmin()) {
            return route('admin.billing.portal');
        }

        return route('organization.billing.portal', [
            'organization' => $tenantContext->organizationId(),
        ]);
    }
}