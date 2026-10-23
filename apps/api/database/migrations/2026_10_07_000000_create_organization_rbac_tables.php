<?php

use App\Application\Authorization\AuthorizationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('description')->nullable();
            $table->timestampsTz();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestampsTz();
            $table->unique(['organization_id', 'slug']);
        });
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained('roles')->restrictOnDelete();
            $table->foreignUuid('permission_id')->constrained('permissions')->restrictOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });
        Schema::create('organization_membership_roles', function (Blueprint $table) {
            $table->foreignUuid('organization_membership_id')->constrained('organization_memberships')->restrictOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->restrictOnDelete();
            $table->unique(['organization_membership_id', 'role_id']);
        });
        app(AuthorizationService::class)->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_membership_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
