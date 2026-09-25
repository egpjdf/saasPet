<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->string('purpose'); // terms_acceptance, privacy_policy, marketing, analytics, etc.
            $table->string('legal_basis'); // consent, contract, legal_obligation, vital_interests, public_task, legitimate_interest
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('version', 20)->default('1.0');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'purpose', 'revoked_at']);
            $table->index(['organization_id', 'purpose', 'granted_at']);
            $table->index(['workspace_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_logs');
    }
};