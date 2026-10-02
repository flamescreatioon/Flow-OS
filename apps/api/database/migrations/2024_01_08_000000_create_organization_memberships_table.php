<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('role', 50)->default('viewer');
            $table->string('status', 20)->default('active');
            $table->timestampTz('joined_at')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        $now = now();
        DB::table('users')->orderBy('id')->each(function (object $user) use ($now): void {
            DB::table('organization_memberships')->insert([
                'id' => Uuid::uuid7()->toString(),
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'role' => $user->role,
                'status' => 'active',
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_memberships');
    }
};
