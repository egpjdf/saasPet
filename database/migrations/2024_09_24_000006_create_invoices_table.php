<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('stripe_id')->nullable()->unique();
            $table->string('paddle_id')->nullable()->unique();
            $table->string('number');
            $table->string('status'); // draft, open, paid, void, uncollectible
            $table->string('currency', 3)->default('BRL');
            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('tax_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);
            $table->unsignedInteger('amount_paid_cents')->default(0);
            $table->unsignedInteger('amount_due_cents')->default(0);
            $table->string('billing_reason')->nullable(); // subscription_cycle, subscription_create, etc.
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('hosted_invoice_url')->nullable();
            $table->string('invoice_pdf')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['subscription_id', 'status']);
            $table->index('stripe_id');
            $table->index('paddle_id');
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};