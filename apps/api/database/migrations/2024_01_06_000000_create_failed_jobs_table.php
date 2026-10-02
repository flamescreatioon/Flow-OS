<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->jsonb('payload');
            $table->text('exception');
            $table->timestampTz('failed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
