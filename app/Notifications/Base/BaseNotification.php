<?php

declare(strict_types=1);

namespace App\Notifications\Base;

use App\Models\User;
use App\Models\Notifications\NotificationPreference;
use App\Models\Notifications\Notification;
use App\Services\Tenant\HasTenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;

abstract class BaseNotification extends LaravelNotification implements ShouldQueue
{
    use Queueable, HasTenantContext;

    protected string $type;
    protected array $defaultChannels = ['mail', 'database'];
    protected bool $tenantAware = true;

    public function __construct(
        protected array $data = [],
    ) {}

    public function via($notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['mail'];
        }

        $tenantContext = app(\App\Services\Tenant\TenantContext::class);
        $channels = [];

        // Check user preferences for each channel
        foreach ($this->getAvailableChannels($notifiable) as $channel) {
            $preference = NotificationPreference::where('user_id', $notifiable->id)
                ->where('type', $this->type)
                ->where('channel', $channel)
                ->where('enabled', true)
                ->first();

            // If no preference exists, use default
            if (! $preference) {
                if (in_array($channel, $this->defaultChannels, true)) {
                    $channels[] = $channel;
                }
                continue;
            }

            if ($preference->enabled) {
                $channels[] = $channel;
            }
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);
        $organization = $tenantContext->hasOrganization()
            ? \App\Models\Organization::find($tenantContext->organizationId())
            : null;

        $message = (new MailMessage)
            ->subject($this->getSubject($notifiable))
            ->greeting($this->getGreeting($notifiable))
            ->line($this->getMessage($notifiable));

        $actionUrl = $this->getActionUrl($notifiable);
        $actionText = $this->getActionText($notifiable);

        if ($actionUrl && $actionText) {
            $message->action($actionText, $actionUrl);
        }

        $message->line($this->getFooter($notifiable, $organization));

        return $message;
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->getSubject($notifiable),
            'message' => $this->getMessage($notifiable),
            'data' => $this->data,
            'action_url' => $this->getActionUrl($notifiable),
            'action_text' => $this->getActionText($notifiable),
        ];
    }

    public function toDatabase($notifiable): array
    {
        // Store in notifications table
        $notification = Notification::create([
            'user_id' => $notifiable->id,
            'organization_id' => $notifiable->organization_id,
            'workspace_id' => $notifiable->workspace_id,
            'type' => $this->type,
            'channel' => 'database',
            'title' => $this->getSubject($notifiable),
            'message' => $this->getMessage($notifiable),
            'data' => $this->toArray($notifiable),
            'unsubscribe_token' => \Illuminate\Support\Str::random(64),
        ]);

        // Broadcast via Reverb
        broadcast(new \App\Events\NotificationCreated($notification))->toOthers();

        return $this->toArray($notifiable);
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    // Abstract methods to be implemented by subclasses
    abstract protected function getSubject($notifiable): string;
    abstract protected function getMessage($notifiable): string;

    // Optional methods with defaults
    protected function getGreeting($notifiable): string
    {
        return 'Olá ' . ($notifiable->name ?? 'Usuário') . ',';
    }

    protected function getActionUrl($notifiable): ?string
    {
        return null;
    }

    protected function getActionText($notifiable): ?string
    {
        return null;
    }

    protected function getFooter($notifiable, $organization = null): string
    {
        $orgName = $organization?->name ?? 'Saaspet';
        return "Atenciosamente,\nEquipe {$orgName}";
    }

    protected function getAvailableChannels($notifiable): array
    {
        return ['mail', 'database', 'reverb'];
    }

    protected function shouldSend($notifiable): bool
    {
        return true;
    }
}