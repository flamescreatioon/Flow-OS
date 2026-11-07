<?php

use App\Application\Authorization\AuthorizationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            // A branch is context on a commercial document, not a stock or
            // ownership boundary.
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // A customer is optional (a walk-in bill). Customers are archived
            // rather than deleted while an invoice references them.
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->string('customer_name', 200)->nullable();
            // The number is assigned at issue time; drafts have none. Both
            // columns are nullable so multiple drafts coexist under the
            // organization-scoped unique constraints.
            $table->unsignedBigInteger('invoice_number')->nullable();
            $table->string('document_number', 50)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('voided_at')->nullable();
            // The due date is a calendar date, never a client instant.
            $table->date('due_on')->nullable();
            // The currency snapshot of the organization at issue time.
            $table->char('currency', 3)->default('NGN');
            // The tax snapshot at issue time: the percentage is at the unit
            // price scale (7.5000 for 7.5%).
            $table->bigInteger('tax_rate_percent')->nullable();
            $table->string('tax_mode', 20)->nullable();
            // Document totals at the unit price scale, computed by the
            // server. In exclusive mode grand_total = subtotal + tax_total;
            // in inclusive mode the tax sits inside the line totals and
            // subtotal = grand_total - tax_total.
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount_total')->default(0);
            $table->bigInteger('tax_total')->default(0);
            $table->bigInteger('grand_total')->default(0);
            $table->string('note')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'document_number']);
            $table->unique(['organization_id', 'invoice_number']);
            $table->index('organization_id');
            $table->index(['organization_id', 'status']);
            $table->index('customer_id');
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('invoice_id')->constrained('invoices')->restrictOnDelete();
            // A line may reference a catalogue product or be free-text; when
            // set, the product must belong to the same organization.
            $table->foreignUuid('product_id')->nullable()->constrained('products')->restrictOnDelete();
            $table->string('description', 300);
            // The billed magnitude in thousandths of a unit of measure.
            $table->bigInteger('quantity');
            // The unit price in the organization currency, scaled by the unit
            // price scale. Snapshotted at issue.
            $table->bigInteger('unit_price');
            // A per-line discount amount at the unit price scale.
            $table->bigInteger('discount')->default(0);
            // The computed tax amount at the unit price scale (zero when tax
            // is disabled or the invoice is not yet issued).
            $table->bigInteger('tax_amount')->default(0);
            // quantity x unit_price - discount, rounded half-up to the unit
            // price scale. In inclusive mode the tax sits inside this value.
            $table->bigInteger('line_total');
            $table->timestampsTz();

            $table->index(['organization_id', 'product_id']);
            $table->index('invoice_id');
        });

        // Existing organizations need the new invoicing permissions granted
        // to their system roles.
        app(AuthorizationService::class)->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
