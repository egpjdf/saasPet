<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Billing\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\Tenant\HasTenantContext;

class TrialWillEnd extends Notification implements ShouldQueue
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
        $daysRemaining = $this->subscription->trialDaysRemaining();

        return (new MailMessage)
            ->subject('Seu período de teste termina em ' . $daysRemaining . ' dias')
            ->greeting('Olá ' . $notifiable->name . ',')
            ->line('Seu período de teste do plano ' . $this->subscription->plan?->name . ' termina em ' . $daysRemaining . ' dias.')
            ->line('Após o término, a assinatura será cobrada automaticamente.')
            ->action('Gerenciar assinatura', $this->getBillingPortalUrl())
            ->line('Se não quiser continuar, cancele antes do término do teste.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'trial_will_end',
            'subscription_id' => $this->subscription->id,
            'days_remaining' => $this->subscription->trialDaysRemaining(),
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