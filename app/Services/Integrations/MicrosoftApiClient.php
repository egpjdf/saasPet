<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Integrations\OAuthProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class MicrosoftApiClient extends BaseApiClient
{
    public function __construct(
        OAuthProvider $oauthProvider,
        SocialiteUser $socialUser,
    ) {
        parent::__construct(
            'https://graph.microsoft.com/v1.0',
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

        $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'client_id' => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
            'scope' => implode(' ', $oauthProvider->scopes ?? ['https://graph.microsoft.com/.default']),
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to refresh Microsoft access token');
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];
        $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600);
    }

    // Microsoft Graph methods
    public function getUser(): array
    {
        return $this->get('/me');
    }

    public function getEmails(array $params = []): array
    {
        return $this->get('/me/messages', $params);
    }

    public function sendEmail(array $emailData): array
    {
        return $this->post('/me/sendMail', $emailData);
    }

    public function getCalendarEvents(array $params = []): array
    {
        return $this->get('/me/events', $params);
    }
}