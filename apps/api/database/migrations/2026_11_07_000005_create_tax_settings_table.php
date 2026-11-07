<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Organization-level tax configuration (ADR 0036). The rate is a
        // percentage at the unit price scale (7.5000 for 7.5%); the invoice
        // snapshots it at issue time, so changing this row never rewrites
        // issued invoices.
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->boolean('tax_enabled')->default(false);
            $table->bigInteger('tax_rate_percent')->nullable();
            $table->string('tax_mode', 20)->default('exclusive');
            $table->timestampsTz();

            $table->unique('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
