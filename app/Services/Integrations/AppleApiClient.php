<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Integrations\OAuthProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class AppleApiClient extends BaseApiClient
{
    public function __construct(
        OAuthProvider $oauthProvider,
        SocialiteUser $socialUser,
    ) {
        parent::__construct(
            'https://api.apple.com',
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

        // Apple uses JWT for client secret
        $clientSecret = $this->generateAppleClientSecret();

        $response = Http::asForm()->post('https://appleid.apple.com/auth/token', [
            'client_id' => config('services.apple.client_id'),
            'client_secret' => $clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to refresh Apple access token');
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];
        $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600);
    }

    private function generateAppleClientSecret(): string
    {
        // Generate JWT for Apple client secret
        // This requires the private key from Apple Developer account
        // Implementation depends on your Apple Developer setup
        return config('services.apple.client_secret');
    }

    // Apple methods (limited - mainly for Sign in with Apple)
    public function getUserInfo(): array
    {
        // Apple doesn't provide a user info endpoint like other providers
        // User info comes from the ID token during authentication
        return [];
    }
}