<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->char('country_code', 2)->default('NG')->after('slug');
            $table->char('currency_code', 3)->default('NGN')->after('currency');
            $table->string('tax_identification_number', 100)->nullable()->after('registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'currency_code', 'tax_identification_number']);
        });
    }
};
