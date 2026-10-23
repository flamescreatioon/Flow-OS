<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            // A location may sit inside a branch of the same organization. A
            // location is not interchangeable with a branch.
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUuid('location_id')->constrained('inventory_locations')->restrictOnDelete();
            // A correction points at the movement it reverses, and a transfer
            // points at its other half. The constraints are added below.
            $table->uuid('corrects_movement_id')->nullable();
            $table->uuid('transfer_movement_id')->nullable();
            $table->string('type', 20);
            // The signed quantity in thousandths of a unit of measure. A
            // movement is immutable, so this value is never updated.
            $table->bigInteger('quantity');
            // The unit cost in the organization currency, scaled by the unit
            // price scale. Valuation is a later accounting decision.
            $table->bigInteger('unit_cost')->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('note')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampsTz();

            $table->index(['organization_id', 'occurred_at']);
            $table->index(['product_id', 'location_id']);
            $table->index('corrects_movement_id');
        });

        // The primary key is added after the create statement on PostgreSQL, so
        // a self referencing constraint is only valid once it exists.
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('corrects_movement_id')->references('id')->on('stock_movements')->nullOnDelete();
            $table->foreign('transfer_movement_id')->references('id')->on('stock_movements')->nullOnDelete();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('location_id')->constrained('inventory_locations')->cascadeOnDelete();
            // The derived quantity in thousandths of a unit of measure. This
            // is a read model, maintained from the movement records.
            $table->bigInteger('quantity')->default(0);
            $table->timestampsTz();

            $table->unique(['product_id', 'location_id']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_locations');
    }
};
