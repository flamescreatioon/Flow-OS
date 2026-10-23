<?php

use App\Application\Authorization\AuthorizationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            // The number of decimal places a client may use for quantities of
            // this unit. The stored scale is fixed by the platform.
            $table->unsignedTinyInteger('precision')->default(0);
            $table->timestampsTz();

            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('description')->nullable();
            $table->timestampsTz();

            $table->index('organization_id');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('unit_of_measure_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 200);
            $table->string('sku', 100)->nullable();
            $table->string('type', 20);
            $table->string('tax_code', 50)->nullable();
            // The list price in the organization currency, scaled by the unit
            // price scale. A product is never deleted because a movement may
            // reference it, so it is archived instead.
            $table->bigInteger('price')->default(0);
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'sku']);
            $table->index('organization_id');
            $table->index(['organization_id', 'type']);
        });

        // Existing organizations need the new catalogue and stock permissions
        // granted to their system roles.
        app(AuthorizationService::class)->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('unit_of_measures');
    }
};
