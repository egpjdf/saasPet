<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Integrations\OAuthProvider;
use Illuminate\Support\Facades\Log;

class ApiClientFactory
{
    public function make(string $provider, OAuthProvider $oauthProvider, \Laravel\Socialite\Contracts\User $socialUser): BaseApiClient
    {
        return match ($provider) {
            'google' => new GoogleApiClient($oauthProvider, $socialUser),
            'microsoft' => new MicrosoftApiClient($oauthProvider, $socialUser),
            'github' => new GitHubApiClient($oauthProvider, $socialUser),
            'apple' => new AppleApiClient($oauthProvider, $socialUser),
            default => throw new \Exception("Unsupported OAuth provider: {$provider}"),
        };
    }
}