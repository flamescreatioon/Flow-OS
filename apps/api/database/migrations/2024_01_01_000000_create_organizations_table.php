<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('legal_name', 255)->nullable();
            $table->string('business_type', 100)->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->string('slug', 100)->unique();
            $table->string('timezone', 50)->default('Africa/Lagos');
            $table->char('currency', 3)->default('NGN');
            $table->string('phone', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->jsonb('settings')->default('{}');
            $table->string('status', 20)->default('active')->index();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
