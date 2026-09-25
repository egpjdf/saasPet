<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notifications\Notification;
use App\Models\Notifications\NotificationPreference;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = [
            'unread_only' => $request->boolean('unread_only'),
            'type' => $request->input('type'),
            'channel' => $request->input('channel'),
            'per_page' => $request->integer('per_page', 20),
        ];

        $notifications = $this->notificationService->getUserNotifications($user, $filters);

        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $this->notificationService->getUnreadCount($user),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'data' => new NotificationResource($notification),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $success = $this->notificationService->markAsRead($user, $id);

        if (! $success) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'message' => 'All notifications marked as read',
            'count' => $count,
        ]);
    }

    public function archive(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $success = $this->notificationService->archive($user, $id);

        if (! $success) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        return response()->json(['message' => 'Notification archived']);
    }

    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();

        $preferences = $this->notificationService->getPreferences($user);

        return response()->json([
            'data' => $preferences,
        ]);
    }

    public function updatePreference(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string'],
            'channel' => ['required', 'string', 'in:mail,sms,push,in_app,reverb'],
            'enabled' => ['required', 'boolean'],
            'frequency' => ['nullable', 'string', 'in:immediate,daily_digest,weekly_digest'],
        ]);

        $user = $request->user();

        $preference = $this->notificationService->updatePreference(
            $user,
            $request->input('type'),
            $request->input('channel'),
            $request->boolean('enabled'),
            $request->input('frequency', 'immediate'),
        );

        return response()->json([
            'data' => $preference,
        ]);
    }

    public function unsubscribe(Request $request, string $token): JsonResponse
    {
        $notification = Notification::where('unsubscribe_token', $token)->first();

        if (! $notification) {
            return response()->json(['error' => 'Invalid unsubscribe token'], 404);
        }

        // Update preference to disable this channel for this type
        $preference = NotificationPreference::where('user_id', $notification->user_id)
            ->where('type', $notification->type)
            ->where('channel', $notification->channel)
            ->first();

        if ($preference) {
            $preference->update(['enabled' => false]);
        }

        return response()->json([
            'message' => 'Successfully unsubscribed',
        ]);
    }
}