<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('password', 255);
            $table->string('role', 50)->default('viewer');
            $table->boolean('is_active')->default(true);
            $table->timestampTz('email_verified_at')->nullable();
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['organization_id', 'email'])->whereNull('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
