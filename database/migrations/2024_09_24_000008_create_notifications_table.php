<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->string('type');
            $table->string('channel');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('unsubscribe_token', 64)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'read_at', 'archived_at']);
            $table->index(['organization_id', 'workspace_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index('unsubscribe_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};