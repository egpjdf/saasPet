<?php

declare(strict_types=1);

namespace App\Jobs\LGPD;

use App\Models\LGPD\RetentionPolicy;
use App\Models\Organization;
use App\Services\LGPD\DataDeletionService;
use App\Services\Tenant\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RetentionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $policies = RetentionPolicy::where('enabled', true)->get();

        foreach ($policies as $policy) {
            try {
                $this->applyPolicy($policy);
            } catch (\Throwable $e) {
                Log::error('Retention policy failed', [
                    'policy_id' => $policy->id,
                    'resource_type' => $policy->resource_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Retention job completed', ['policies_processed' => $policies->count()]);
    }

    private function applyPolicy(RetentionPolicy $policy): void
    {
        $cutoffDate = now()->subDays($policy->retention_days);

        switch ($policy->resource_type) {
            case 'audit_logs':
                $this->processAuditLogs($policy, $cutoffDate);
                break;
            case 'notifications':
                $this->processNotifications($policy, $cutoffDate);
                break;
            case 'webhook_deliveries':
                $this->processWebhookDeliveries($policy, $cutoffDate);
                break;
            case 'invoices':
                $this->processInvoices($policy, $cutoffDate);
                break;
            case 'consent_logs':
                $this->processConsentLogs($policy, $cutoffDate);
                break;
        }
    }

    private function processAuditLogs(RetentionPolicy $policy, $cutoffDate): void
    {
        $query = DB::table('audit_logs')
            ->where('created_at', '<', $cutoffDate);

        if ($policy->organization_id) {
            $query->where('organization_id', $policy->organization_id);
        }

        if ($policy->workspace_id) {
            $query->where('workspace_id', $policy->workspace_id);
        }

        match ($policy->action) {
            'anonymize' => $query->update([
                'user_id' => null,
                'user_name' => 'Anonimizado',
                'user_email' => 'anonymized@local',
                'metadata' => DB::raw("jsonb_set(metadata::jsonb, '{retention_anonymized}', 'true')"),
            ]),
            'delete' => $query->delete(),
            'archive' => $query->update(['archived_at' => now()]),
        };
    }

    private function processNotifications(RetentionPolicy $policy, $cutoffDate): void
    {
        $query = DB::table('notifications')
            ->where('created_at', '<', $cutoffDate)
            ->whereNull('archived_at');

        if ($policy->organization_id) {
            $query->where('organization_id', $policy->organization_id);
        }

        if ($policy->workspace_id) {
            $query->where('workspace_id', $policy->workspace_id);
        }

        match ($policy->action) {
            'delete' => $query->delete(),
            'archive' => $query->update(['archived_at' => now()]),
            default => null,
        };
    }

    private function processWebhookDeliveries(RetentionPolicy $policy, $cutoffDate): void
    {
        $query = DB::table('webhook_deliveries')
            ->where('created_at', '<', $cutoffDate);

        if ($policy->organization_id) {
            $query->where('organization_id', $policy->organization_id);
        }

        if ($policy->workspace_id) {
            $query->where('workspace_id', $policy->workspace_id);
        }

        match ($policy->action) {
            'delete' => $query->delete(),
            'archive' => $query->update(['archived_at' => now()]),
            default => null,
        };
    }

    private function processInvoices(RetentionPolicy $policy, $cutoffDate): void
    {
        $query = DB::table('invoices')
            ->where('created_at', '<', $cutoffDate);

        if ($policy->organization_id) {
            $query->where('organization_id', $policy->organization_id);
        }

        if ($policy->workspace_id) {
            $query->where('workspace_id', $policy->workspace_id);
        }

        match ($policy->action) {
            'archive' => $query->update(['archived_at' => now()]),
            default => null,
        };
    }

    private function processConsentLogs(RetentionPolicy $policy, $cutoffDate): void
    {
        $query = DB::table('consent_logs')
            ->where('created_at', '<', $cutoffDate);

        if ($policy->organization_id) {
            $query->where('organization_id', $policy->organization_id);
        }

        if ($policy->workspace_id) {
            $query->where('workspace_id', $policy->workspace_id);
        }

        match ($policy->action) {
            'archive' => $query->update(['archived_at' => now()]),
            default => null,
        };
    }
}