<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->string('secret', 64);
            $table->json('events');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('last_delivery_at')->nullable();
            $table->unsignedInteger('last_status_code')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'active']);
            $table->index(['workspace_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};