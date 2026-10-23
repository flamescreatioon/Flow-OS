<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The shared secret is encrypted at rest by the model cast, so it
            // is stored as ciphertext rather than as a readable base32 value.
            $table->text('mfa_secret')->nullable();
            $table->text('mfa_pending_secret')->nullable();
            $table->jsonb('mfa_recovery_codes')->nullable();
            $table->timestampTz('mfa_enrolled_at')->nullable();
            // The last accepted time-step counter prevents a code from being
            // replayed inside its own validity window.
            $table->bigInteger('mfa_last_used_counter')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mfa_secret',
                'mfa_pending_secret',
                'mfa_recovery_codes',
                'mfa_enrolled_at',
                'mfa_last_used_counter',
            ]);
        });
    }
};
