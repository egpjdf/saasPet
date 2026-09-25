<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('user_id');
            $table->uuid('organization_id'); // denormalized for RLS
            $table->string('role')->default('member');
            $table->string('status')->default('pending'); // pending, active, revoked
            $table->uuid('invited_by')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('workspace_id')
                ->references('id')
                ->on('workspaces')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();

            $table->foreign('invited_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['workspace_id', 'user_id']);
            $table->index(['organization_id', 'workspace_id']);
            $table->index(['organization_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_users');
    }
};