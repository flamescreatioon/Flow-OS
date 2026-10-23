<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 100);
            $table->string('entity_type', 100);
            $table->uuid('entity_id')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('created_at')->nullable();

            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
