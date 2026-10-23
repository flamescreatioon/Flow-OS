<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mfa_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            // Only the hash of the challenge is stored, so a leaked table
            // cannot be redeemed.
            $table->string('challenge_hash', 64);
            $table->string('purpose', 20);
            $table->string('client_name')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('expires_at');
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();

            $table->unique('challenge_hash');
            $table->index(['user_id', 'expires_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfa_challenges');
    }
};
