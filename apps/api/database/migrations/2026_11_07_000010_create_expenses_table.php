<?php

use App\Application\Authorization\AuthorizationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            // A branch is optional context on an operational expense.
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // A category is master data: archived, never deleted while an
            // expense references it.
            $table->foreignUuid('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->unsignedBigInteger('expense_number');
            $table->string('document_number', 50);
            // Recorded expenses are editable; void is terminal and keeps the
            // record as evidence (ADR 0039). There is no delete path.
            $table->string('status', 20)->default('recorded');
            $table->timestampTz('voided_at')->nullable();
            $table->string('description', 300);
            // The expense amount in the organization currency, scaled by the
            // unit price scale. Always a positive magnitude, set by the server
            // from the validated input.
            $table->bigInteger('amount');
            // The calendar date the money was spent, in the organization
            // timezone context. A DATE, never a client-supplied instant.
            $table->date('spent_on');
            // The payee direction is free text in this slice; linking payees
            // to supplier records is a later decision.
            $table->string('payee', 200)->nullable();
            $table->string('payment_method', 20)->default('cash');
            $table->string('note')->nullable();
            $table->timestampsTz();

            $table->unique(['organization_id', 'document_number']);
            $table->unique(['organization_id', 'expense_number']);
            $table->index('organization_id');
            $table->index(['organization_id', 'spent_on']);
            $table->index(['organization_id', 'status']);
            $table->index('expense_category_id');
        });

        // Existing organizations need the new finance permissions granted to
        // their system roles.
        app(AuthorizationService::class)->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
