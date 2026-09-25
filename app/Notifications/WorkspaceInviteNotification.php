<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WorkspaceUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class WorkspaceInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly WorkspaceUser $workspaceUser,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->inviteUrl();

        return (new MailMessage)
            ->subject("Convite para {$this->workspaceUser->workspace->name}")
            ->greeting("Olá,")
            ->line("Você foi convidado por {$this->workspaceUser->inviter?->name} para participar do workspace {$this->workspaceUser->workspace->name} na organização {$this->workspaceUser->workspace->organization->name}.")
            ->line("Seu papel será: {$this->workspaceUser->role->label()}")
            ->action('Aceitar Convite', $url)
            ->line('Este convite expira em 7 dias.')
            ->salutation('Atenciosamente, Equipe Saaspet');
    }

    public function inviteUrl(): string
    {
        return URL::temporarySignedRoute(
            'api.workspace.invite.accept',
            now()->addDays(7),
            ['workspace_user' => $this->workspaceUser->id]
        );
    }
}