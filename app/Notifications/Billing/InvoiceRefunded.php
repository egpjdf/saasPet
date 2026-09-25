<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Billing\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\Tenant\HasTenantContext;

class InvoiceRefunded extends Notification implements ShouldQueue
{
    use Queueable, HasTenantContext;

    public function __construct(
        public Subscription $subscription,
        public array $refundData,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $amount = ($this->refundData['details']['total'] ?? 0) / 100;

        return (new MailMessage)
            ->subject('Reembolso processado')
            ->greeting('Olá ' . $notifiable->name . ',')
            ->line('Um reembolso de ' . number_format($amount, 2, ',', '.') . ' ' . strtoupper($this->refundData['currency']) . ' foi processado.')
            ->line('Motivo: ' . ($this->refundData['details']['refund_reason'] ?? 'Não informado'))
            ->action('Ver detalhes', $this->getBillingPortalUrl());
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'invoice_refunded',
            'subscription_id' => $this->subscription->id,
            'amount' => $this->refundData['details']['total'] ?? 0,
            'currency' => $this->refundData['currency'],
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