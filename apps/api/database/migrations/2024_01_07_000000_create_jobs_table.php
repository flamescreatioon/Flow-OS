<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue', 255);
            $table->jsonb('payload');
            $table->smallInteger('attempts')->default(0);
            $table->timestampTz('reserved_at')->nullable();
            $table->timestampTz('available_at')->nullable();
            $table->timestampTz('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
