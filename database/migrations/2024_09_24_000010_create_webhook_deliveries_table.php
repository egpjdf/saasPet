<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->unsignedInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedInteger('attempt');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'endpoint_id', 'delivered_at']);
            $table->index(['endpoint_id', 'attempt']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};