<?php

declare(strict_types=1);

namespace App\Services\LGPD;

use App\Models\LGPD\ConsentLog;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DataExportService
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {}

    public function exportUserData(User $user, string $format = 'json'): array
    {
        $tenantContext = $this->tenantContext;

        if (! $tenantContext->hasOrganization()) {
            throw new \Exception('Organization context required');
        }

        // Verify user belongs to current tenant
        if ((string) $user->organization_id !== $tenantContext->organizationId()) {
            throw new \Exception('User not in current organization');
        }

        if ($tenantContext->hasWorkspace() && (string) $user->workspace_id !== $tenantContext->workspaceId()) {
            throw new \Exception('User not in current workspace');
        }

        $data = [
            'profile' => $this->getProfileData($user),
            'consents' => $this->getConsentData($user),
            'activities' => $this->getActivityData($user),
            'billing' => $this->getBillingData($user),
            'notifications' => $this->getNotificationData($user),
            'audit_logs' => $this->getAuditLogData($user),
        ];

        $filename = "data_export_{$user->id}_" . now()->format('Ymd_His');

        if ($format === 'json') {
            $filepath = $this->saveJson($data, $filename);
        } elseif ($format === 'pdf') {
            $filepath = $this->savePdf($data, $filename, $user);
        } else {
            throw new \Exception('Unsupported format: ' . $format);
        }

        // Log export request
        Log::info('LGPD data export completed', [
            'user_id' => $user->id,
            'format' => $format,
            'file' => $filepath,
        ]);

        return [
            'filepath' => $filepath,
            'filename' => basename($filepath),
            'format' => $format,
            'generated_at' => now()->toISOString(),
        ];
    }

    public function exportOrganizationData(Organization $organization, string $format = 'json'): array
    {
        $tenantContext = $this->tenantContext;

        if (! $tenantContext->isPlatformAdmin() && (string) $tenantContext->organizationId() !== (string) $organization->id) {
            throw new \Exception('Access denied');
        }

        $data = [
            'organization' => $organization->toArray(),
            'workspaces' => $organization->workspaces->toArray(),
            'users' => $organization->users->map(fn ($u) => $this->getProfileData($u))->toArray(),
            'consents' => ConsentLog::where('organization_id', $organization->id)->get()->toArray(),
            'billing' => $this->getOrganizationBillingData($organization),
        ];

        $filename = "org_data_export_{$organization->id}_" . now()->format('Ymd_His');

        if ($format === 'json') {
            $filepath = $this->saveJson($data, $filename);
        } else {
            $filepath = $this->savePdf($data, $filename);
        }

        return [
            'filepath' => $filepath,
            'filename' => basename($filepath),
            'format' => $format,
            'generated_at' => now()->toISOString(),
        ];
    }

    private function getProfileData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }

    private function getConsentData(User $user): array
    {
        return ConsentLog::where('user_id', $user->id)
            ->orderBy('granted_at', 'desc')
            ->get()
            ->toArray();
    }

    private function getActivityData(User $user): array
    {
        // Get audit logs for this user
        return DB::table('audit_logs')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get()
            ->toArray();
    }

    private function getBillingData(User $user): array
    {
        return [
            'subscriptions' => $user->subscriptions()
                ->with('plan')
                ->get()
                ->toArray(),
            'invoices' => $user->invoices()
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get()
                ->toArray(),
        ];
    }

    private function getNotificationData(User $user): array
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get()
            ->toArray();
    }

    private function getAuditLogData(User $user): array
    {
        return DB::table('audit_logs')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get()
            ->toArray();
    }

    private function getOrganizationBillingData(Organization $organization): array
    {
        return [
            'subscriptions' => \App\Models\Billing\Subscription::where('organization_id', $organization->id)
                ->with('plan')
                ->get()
                ->toArray(),
            'invoices' => \App\Models\Billing\Invoice::where('organization_id', $organization->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray(),
        ];
    }

    private function saveJson(array $data, string $filename): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $path = "exports/{$filename}.json";

        Storage::disk('local')->put($path, $json);

        return storage_path("app/{$path}");
    }

    private function savePdf(array $data, string $filename, User $user = null): string
    {
        // Generate PDF using DomPDF or similar
        // For now, save as JSON with .pdf extension placeholder
        $path = "exports/{$filename}.pdf";

        $content = $this->generatePdfContent($data, $user);
        Storage::disk('local')->put($path, $content);

        return storage_path("app/{$path}");
    }

    private function generatePdfContent(array $data, User $user = null): string
    {
        // This would use a PDF library like DomPDF
        // Placeholder implementation
        return "PDF Export for " . ($user?->name ?? 'Organization') . "\n" .
               "Generated at: " . now()->toISOString() . "\n\n" .
               json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}