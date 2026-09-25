<?php

declare(strict_types=1);

namespace App\Http\Controllers\LGPD;

use App\Http\Controllers\Controller;
use App\Models\LGPD\ConsentLog;
use App\Models\LGPD\RetentionPolicy;
use App\Models\Organization;
use App\Models\User;
use App\Services\LGPD\DataDeletionService;
use App\Services\LGPD\DataExportService;
use App\Services\LGPD\DpaGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LGPDController extends Controller
{
    public function __construct(
        private DataExportService $exportService,
        private DataDeletionService $deletionService,
        private DpaGenerator $dpaGenerator,
    ) {}

    // Consent management
    public function recordConsent(Request $request): JsonResponse
    {
        $request->validate([
            'purpose' => ['required', 'string', 'max:100'],
            'legal_basis' => ['required', 'string', 'in:consent,contract,legal_obligation,vital_interests,public_task,legitimate_interest'],
            'version' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        $consent = ConsentLog::create([
            'user_id' => $user->id,
            'organization_id' => $tenantContext->organizationId(),
            'workspace_id' => $tenantContext->workspaceId(),
            'purpose' => $request->input('purpose'),
            'legal_basis' => $request->input('legal_basis'),
            'granted_at' => now(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'version' => $request->input('version', '1.0'),
        ]);

        return response()->json([
            'data' => $consent,
            'message' => 'Consentimento registrado',
        ], 201);
    }

    public function revokeConsent(Request $request, string $consentId): JsonResponse
    {
        $user = $request->user();
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        $consent = ConsentLog::where('user_id', $user->id)
            ->where('organization_id', $tenantContext->organizationId())
            ->where('id', $consentId)
            ->firstOrFail();

        $consent->revoke();

        return response()->json([
            'message' => 'Consentimento revogado',
        ]);
    }

    public function getConsents(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        $consents = ConsentLog::where('user_id', $user->id)
            ->where('organization_id', $tenantContext->organizationId())
            ->orderBy('granted_at', 'desc')
            ->get();

        return response()->json([
            'data' => $consents,
        ]);
    }

    // Data export
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => ['nullable', 'string', 'in:json,pdf'],
            'user_id' => ['nullable', 'string'], // For org admins
        ]);

        $user = $request->user();
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        // Determine target user
        if ($request->filled('user_id') && $user->canManageUsers()) {
            $targetUser = User::where('organization_id', $tenantContext->organizationId())
                ->findOrFail($request->input('user_id'));
        } else {
            $targetUser = $user;
        }

        $format = $request->input('format', 'json');
        $result = $this->exportService->exportUserData($targetUser, $format);

        return response()->json($result);
    }

    public function exportOrganization(Request $request): JsonResponse
    {
        $request->validate([
            'format' => ['nullable', 'string', 'in:json,pdf'],
        ]);

        $user = $request->user();
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        if (! $user->isOrgAdmin() && ! $user->isPlatformAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $organization = Organization::findOrFail($tenantContext->organizationId());
        $format = $request->input('format', 'json');

        $result = $this->exportService->exportOrganizationData($organization, $format);

        return response()->json($result);
    }

    // Data deletion
    public function requestDeletion(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['nullable', 'string'], // For org admins
            'confirm' => ['required', 'accepted'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        // Determine target user
        if ($request->filled('user_id') && $user->canManageUsers()) {
            $targetUser = User::where('organization_id', $tenantContext->organizationId())
                ->findOrFail($request->input('user_id'));
        } else {
            $targetUser = $user;
        }

        // Cannot delete platform admin
        if ($targetUser->isPlatformAdmin()) {
            return response()->json(['error' => 'Cannot delete platform admin'], 403);
        }

        $result = $this->deletionService->anonymizeUser($targetUser);

        return response()->json([
            'message' => 'Solicitação de exclusão processada',
            'data' => $result,
        ]);
    }

    // DPA
    public function dpa(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        if (! $user->isOrgAdmin() && ! $user->isPlatformAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $organization = Organization::findOrFail($tenantContext->organizationId());
        $filepath = $this->dpaGenerator->generate($organization);

        return response()->json([
            'message' => 'DPA gerado',
            'filepath' => $filepath,
            'download_url' => route('lgpd.dpa.download', ['organization' => $organization]),
        ]);
    }

    public function downloadDpa(Request $request, Organization $organization)
    {
        $user = $request->user();

        if (! $user->isOrgAdmin() && ! $user->isPlatformAdmin()) {
            abort(403);
        }

        if ((string) $user->organization_id !== (string) $organization->id && ! $user->isPlatformAdmin()) {
            abort(403);
        }

        $filepath = $this->dpaGenerator->generate($organization);

        return response()->download($filepath);
    }

    // Retention policies
    public function retentionPolicies(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isOrgAdmin() && ! $user->isPlatformAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        $policies = RetentionPolicy::where('organization_id', $tenantContext->organizationId())
            ->when($tenantContext->hasWorkspace(), fn ($q) => $q->where('workspace_id', $tenantContext->workspaceId()))
            ->get();

        return response()->json([
            'data' => $policies,
        ]);
    }

    public function updateRetentionPolicy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->isOrgAdmin() && ! $user->isPlatformAdmin()) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $request->validate([
            'retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'action' => ['required', 'string', 'in:anonymize,delete,archive'],
            'enabled' => ['boolean'],
        ]);

        $tenantContext = app(\App\Services\Tenant\TenantContext::class);

        $policy = RetentionPolicy::where('organization_id', $tenantContext->organizationId())
            ->findOrFail($id);

        $policy->update($request->only(['retention_days', 'action', 'enabled']));

        return response()->json([
            'data' => $policy,
            'message' => 'Política de retenção atualizada',
        ]);
    }
}