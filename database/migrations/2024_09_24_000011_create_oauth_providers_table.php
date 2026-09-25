<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->string('provider'); // google, microsoft, github, apple
            $table->string('name');
            $table->string('client_id');
            $table->text('client_secret'); // encrypted
            $table->string('redirect_uri');
            $table->json('scopes')->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'workspace_id', 'provider'], 'oauth_providers_org_ws_provider_unique');
            $table->index(['organization_id', 'enabled']);
            $table->index(['workspace_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_providers');
    }
};