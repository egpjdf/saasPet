<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Notification $this */
        return [
            'id' => $this->id,
            'type' => $this->type,
            'channel' => $this->channel,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'read_at' => $this->read_at?->toISOString(),
            'archived_at' => $this->archived_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'unsubscribe_url' => $this->unsubscribe_token
                ? route('notifications.unsubscribe', ['token' => $this->unsubscribe_token])
                : null,
        ];
    }
}