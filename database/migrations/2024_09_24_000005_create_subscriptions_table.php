<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('name')->default('default');
            $table->string('stripe_id')->nullable()->unique();
            $table->string('paddle_id')->nullable()->unique();
            $table->string('stripe_status')->nullable();
            $table->string('paddle_status')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->string('paddle_price_id')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('cancels_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'stripe_status']);
            $table->index(['workspace_id', 'paddle_status']);
            $table->index('stripe_id');
            $table->index('paddle_id');
            $table->index(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};