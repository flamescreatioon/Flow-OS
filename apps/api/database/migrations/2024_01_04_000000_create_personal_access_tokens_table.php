<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tokenable_type', 255);
            $table->uuid('tokenable_id');
            $table->string('name', 255);
            $table->string('token', 64)->unique();
            $table->jsonb('abilities')->default('[]');
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
