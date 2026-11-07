<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sequential document numbers are organization-scoped business
        // identifiers (ADR 0035). A UUID stays the entity identity; this table
        // only hands out the next human-facing number per document type. The
        // row is locked inside the allocating transaction, so the count of
        // consumed numbers equals the count of finalized documents.
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->bigInteger('next_value')->default(1);
            $table->timestampsTz();

            $table->unique(['organization_id', 'document_type']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
