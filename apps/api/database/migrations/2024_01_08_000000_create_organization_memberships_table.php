<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestampTz('joined_at')->nullable();
            $table->timestampTz('suspended_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_memberships');
    }
};
