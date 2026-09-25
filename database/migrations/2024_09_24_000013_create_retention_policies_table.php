<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->string('resource_type'); // users, notifications, audit_logs, invoices, webhook_deliveries, consent_logs
            $table->unsignedInteger('retention_days');
            $table->string('action'); // anonymize, delete, archive
            $table->boolean('enabled')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'workspace_id', 'resource_type'], 'retention_policies_org_ws_resource_unique');
            $table->index(['organization_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_policies');
    }
};