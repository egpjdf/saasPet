<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use App\Enums\UserRole;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Sanctum\PersonalAccessTokenResult;
use Laravel\Sanctum\HasApiTokens;

class CreateNewUser implements CreatesNewUsers
{
    use HasApiTokens;

    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => app(config('fortify.password_validation_rules')),
            'password_confirmation' => ['required', 'same:password'],
            'terms' => ['required', 'accepted'],
            'privacy' => ['required', 'accepted'],
        ])->validate();

        $tenantContext = app(TenantContext::class);

        if ($tenantContext->isPlatformAdmin()) {
            throw new \Exception('Platform admin cannot create users via registration');
        }

        $organizationId = $tenantContext->organizationId();
        $workspaceId = $tenantContext->workspaceId();

        if (! $organizationId) {
            throw new \Exception('Organization context required');
        }

        if (! $workspaceId) {
            throw new \Exception('Workspace context required');
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'role' => UserRole::Member,
            'email_verified_at' => null,
        ]);

        // Log consent
        $this->logConsent($user, $input);

        return $user;
    }

    private function logConsent(User $user, array $input): void
    {
        $user->consentLogs()->create([
            'organization_id' => $user->organization_id,
            'workspace_id' => $user->workspace_id,
            'purpose' => 'terms_acceptance',
            'legal_basis' => 'consent',
            'granted_at' => now(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'version' => '1.0',
        ]);

        $user->consentLogs()->create([
            'organization_id' => $user->organization_id,
            'workspace_id' => $user->workspace_id,
            'purpose' => 'privacy_policy',
            'legal_basis' => 'consent',
            'granted_at' => now(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'version' => '1.0',
        ]);
    }
}