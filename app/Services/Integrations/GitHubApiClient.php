<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Integrations\OAuthProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GitHubApiClient extends BaseApiClient
{
    public function __construct(
        OAuthProvider $oauthProvider,
        SocialiteUser $socialUser,
    ) {
        parent::__construct(
            'https://api.github.com',
            $socialUser->token,
            $socialUser->refreshToken ?? null,
            $socialUser->expiresIn ?? 3600,
        );

        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }

    protected function refreshAccessToken(): void
    {
        // GitHub OAuth tokens don't typically expire for user tokens
        // But we can refresh if we have a refresh token (GitHub Apps)
        if ($this->refreshToken) {
            $response = Http::asForm()->post('https://github.com/login/oauth/access_token', [
                'client_id' => config('services.github.client_id'),
                'client_secret' => config('services.github.client_secret'),
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ], [
                'Accept' => 'application/json',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];
                $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600);
            }
        }
    }

    // GitHub methods
    public function getUser(): array
    {
        return $this->get('/user');
    }

    public function getRepositories(array $params = []): array
    {
        return $this->get('/user/repos', $params);
    }

    public function getRepository(string $owner, string $repo): array
    {
        return $this->get("/repos/{$owner}/{$repo}");
    }

    public function createIssue(string $owner, string $repo, array $issueData): array
    {
        return $this->post("/repos/{$owner}/{$repo}/issues", $issueData);
    }

    public function getPullRequests(string $owner, string $repo, array $params = []): array
    {
        return $this->get("/repos/{$owner}/{$repo}/pulls", $params);
    }
}