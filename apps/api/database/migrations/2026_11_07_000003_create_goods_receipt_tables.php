<?php

use App\Application\Authorization\AuthorizationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            // A receipt may sit inside a branch of the same organization. The
            // branch is context, not a stock concept.
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // A receipt may be recorded without a supplier for lightweight
            // one-off purchases (ADR 0035). The supplier is not deletable
            // while a receipt references it; suppliers are archived instead.
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            // The receiving location is stated once per receipt. Inventory
            // rejects an archived location when the movements are recorded.
            $table->foreignUuid('location_id')->constrained('inventory_locations')->restrictOnDelete();
            $table->string('supplier_name', 200)->nullable();
            $table->unsignedBigInteger('receipt_number');
            $table->string('document_number', 50);
            // Receipts are finalized on creation in this slice. The column
            // exists so a later purchase-order state machine does not change
            // the meaning of existing rows.
            $table->string('status', 20)->default('recorded');
            // The calendar date the goods were received, in the organization
            // timezone context. A DATE, never a client-supplied instant.
            $table->date('received_on');
            $table->string('note')->nullable();
            // The receipt total in the organization currency, at the unit
            // price scale. Computed by the server from the lines.
            $table->bigInteger('total_cost')->default(0);
            $table->timestampsTz();

            $table->unique(['organization_id', 'document_number']);
            $table->unique(['organization_id', 'receipt_number']);
            $table->index('organization_id');
            $table->index(['organization_id', 'received_on']);
            $table->index('supplier_id');
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('goods_receipt_id')->constrained('goods_receipts')->restrictOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            // The product name at receipt time, so history does not depend on
            // the current catalogue entry.
            $table->string('product_name', 200);
            // The received magnitude in thousandths of a unit of measure.
            $table->bigInteger('quantity');
            // The unit cost in the organization currency, scaled by the unit
            // price scale. This is the cost snapshot the movement carries.
            $table->bigInteger('unit_cost');
            // The line total in the organization currency, at the unit price
            // scale: round-half-up(quantity x unit_cost).
            $table->bigInteger('line_total');
            $table->timestampsTz();

            $table->index(['organization_id', 'product_id']);
            $table->index('goods_receipt_id');
        });

        // Existing organizations need the new purchasing permissions granted
        // to their system roles.
        app(AuthorizationService::class)->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
    }
};
