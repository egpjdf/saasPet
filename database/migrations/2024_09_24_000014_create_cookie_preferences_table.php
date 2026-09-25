<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cookie_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->string('session_id', 64)->nullable();
            $table->boolean('essential')->default(true);
            $table->boolean('analytics')->default(false);
            $table->boolean('marketing')->default(false);
            $table->boolean('preferences')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->string('version', 20)->default('1.0');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'consented_at']);
            $table->index(['session_id']);
            $table->index(['organization_id', 'workspace_id', 'consented_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_preferences');
    }
};