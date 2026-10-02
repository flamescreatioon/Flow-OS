<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('is_main')->default(false);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
