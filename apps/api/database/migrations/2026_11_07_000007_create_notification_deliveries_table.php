<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Delivery attempts are tracked per ADR 0037. A row is created with
        // the send request and updated by the queued delivery job. Delivery
        // state never feeds back into business correctness.
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->uuid('entity_id');
            $table->string('channel', 20);
            // The driver that handled the channel, e.g. "mail" or "log".
            $table->string('driver', 30);
            $table->string('recipient', 255);
            $table->string('status', 20)->default('queued');
            // Failure details are redacted of credentials before storage.
            $table->text('error_message')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();

            $table->index(['organization_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
