<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_invitations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('organization_id')->constrained()->restrictOnDelete();
            $t->foreignUuid('invited_by_user_id')->constrained('users')->restrictOnDelete();
            $t->string('email', 255);
            $t->string('token_hash', 64)->unique();
            $t->timestampTz('expires_at');
            $t->timestampTz('accepted_at')->nullable();
            $t->timestampTz('declined_at')->nullable();
            $t->timestampTz('cancelled_at')->nullable();
            $t->timestampsTz();
            $t->index(['organization_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
    }
};
