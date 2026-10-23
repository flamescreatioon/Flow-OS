<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('route', 255);
            $table->string('request_hash', 64);
            $table->string('status', 20)->default('completed');
            $table->integer('response_code')->default(0);
            $table->jsonb('response_body')->default('{}');
            $table->timestampTz('expires_at');
            $table->timestampsTz();

            $table->index('expires_at');
        });

        // A single composite unique index cannot deduplicate rows whose
        // organization_id is NULL: PostgreSQL and SQLite both treat NULL
        // values as distinct, so two requests carrying the same key would
        // both insert. Two partial unique indexes cover the
        // organization-scoped and the organization-less case separately.
        DB::statement(
            'CREATE UNIQUE INDEX idempotency_keys_organization_scoped_unique'
            .' ON idempotency_keys (user_id, organization_id, route, key)'
            .' WHERE organization_id IS NOT NULL'
        );

        DB::statement(
            'CREATE UNIQUE INDEX idempotency_keys_unscoped_unique'
            .' ON idempotency_keys (user_id, route, key)'
            .' WHERE organization_id IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
