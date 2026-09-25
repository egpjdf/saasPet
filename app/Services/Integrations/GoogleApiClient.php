<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Integrations\OAuthProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleApiClient extends BaseApiClient
{
    public function __construct(
        OAuthProvider $oauthProvider,
        SocialiteUser $socialUser,
    ) {
        parent::__construct(
            'https://www.googleapis.com',
            $socialUser->token,
            $socialUser->refreshToken ?? null,
            $socialUser->expiresIn ?? 3600,
        );

        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
        ];
    }

    protected function refreshAccessToken(): void
    {
        if (! $this->refreshToken) {
            throw new \Exception('No refresh token available');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to refresh Google access token');
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];
        $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600);
    }

    // Google-specific methods
    public function getUserInfo(): array
    {
        return $this->get('/oauth2/v2/userinfo');
    }

    public function getCalendarEvents(string $calendarId = 'primary', array $params = []): array
    {
        return $this->get("/calendar/v3/calendars/{$calendarId}/events", $params);
    }

    public function createCalendarEvent(string $calendarId, array $eventData): array
    {
        return $this->post("/calendar/v3/calendars/{$calendarId}/events", $eventData);
    }
}