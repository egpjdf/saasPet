<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->string('channel'); // mail, sms, push, in_app, reverb
            $table->string('type'); // welcome, password_reset, etc.
            $table->boolean('enabled')->default(true);
            $table->string('frequency')->default('immediate'); // immediate, daily_digest, weekly_digest
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'type', 'channel'], 'notification_preferences_user_type_channel_unique');
            $table->index(['user_id', 'enabled']);
            $table->index(['organization_id', 'workspace_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};