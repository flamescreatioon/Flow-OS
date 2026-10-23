<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('platform', 50);
            $table->string('device_name', 255)->nullable();
            $table->timestampTz('last_seen_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
