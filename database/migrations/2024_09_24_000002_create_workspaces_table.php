<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('slug');
            $table->string('name');
            $table->json('settings')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();

            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'status']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};