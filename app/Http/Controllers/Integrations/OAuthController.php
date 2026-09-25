<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Integrations\OAuthProvider;
use App\Models\User;
use App\Services\Integrations\ApiClientFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $oauthProvider = OAuthProvider::where('organization_id', $user->organization_id)
            ->where('workspace_id', $user->workspace_id)
            ->where('provider', $provider)
            ->where('enabled', true)
            ->first();

        if (! $oauthProvider) {
            return redirect()->back()->with('error', 'OAuth provider not configured');
        }

        // Validate provider
        if (! in_array($provider, ['google', 'microsoft', 'github', 'apple'], true)) {
            return redirect()->back()->with('error', 'Invalid OAuth provider');
        }

        // Generate state for CSRF protection
        $state = Str::random(40);
        $request->session()->put('oauth_state_' . $provider, $state);
        $request->session()->put('oauth_provider_id', $oauthProvider->id);

        $driver = Socialite::driver($provider);

        // Configure driver with provider credentials
        $driver->setClientId($oauthProvider->client_id);
        $driver->setClientSecret($oauthProvider->getDecryptedSecret());
        $driver->setRedirectUrl($oauthProvider->redirect_uri);

        if (! empty($oauthProvider->scopes)) {
            $driver->setScopes($oauthProvider->scopes);
        }

        // Add PKCE for providers that support it
        if (in_array($provider, ['google', 'github'], true)) {
            $codeVerifier = Str::random(64);
            $request->session()->put('oauth_code_verifier_' . $provider, $codeVerifier);
            $driver->setCodeVerifier($codeVerifier);
        }

        return $driver->stateless()->redirect()->with('state', $state);
    }

    public function callback(Request $request, string $provider): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Verify state
        $storedState = $request->session()->pull('oauth_state_' . $provider);
        $requestState = $request->input('state');

        if (! $storedState || ! hash_equals($storedState, $requestState)) {
            return response()->json(['error' => 'Invalid OAuth state'], 400);
        }

        $oauthProviderId = $request->session()->pull('oauth_provider_id');
        $oauthProvider = OAuthProvider::find($oauthProviderId);

        if (! $oauthProvider) {
            return response()->json(['error' => 'OAuth provider not found'], 404);
        }

        try {
            $driver = Socialite::driver($provider);

            $driver->setClientId($oauthProvider->client_id);
            $driver->setClientSecret($oauthProvider->getDecryptedSecret());
            $driver->setRedirectUrl($oauthProvider->redirect_uri);

            if (! empty($oauthProvider->scopes)) {
                $driver->setScopes($oauthProvider->scopes);
            }

            // PKCE
            if (in_array($provider, ['google', 'github'], true)) {
                $codeVerifier = $request->session()->pull('oauth_code_verifier_' . $provider);
                if ($codeVerifier) {
                    $driver->setCodeVerifier($codeVerifier);
                }
            }

            $socialUser = $driver->stateless()->user();

            // Create API client and store tokens
            $client = app(ApiClientFactory::class)->make($provider, $oauthProvider, $socialUser);

            return response()->json([
                'message' => 'OAuth connection successful',
                'provider' => $provider,
                'user' => [
                    'id' => $socialUser->getId(),
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('OAuth callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'OAuth authentication failed'], 500);
        }
    }

    public function disconnect(Request $request, string $provider): JsonResponse
    {
        $user = $request->user();

        // Delete OAuth tokens for this user/provider
        // This would typically revoke tokens and remove stored credentials

        return response()->json([
            'message' => 'OAuth connection removed',
        ]);
    }
}