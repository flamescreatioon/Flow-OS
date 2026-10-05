<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('address_line_1', 255)->nullable()->after('email');
            $table->string('address_line_2', 255)->nullable()->after('address_line_1');
            $table->string('city', 100)->nullable()->after('address_line_2');
            $table->string('state', 100)->nullable()->after('city');
            $table->char('country_code', 2)->nullable()->after('state');
        });

        DB::statement('CREATE UNIQUE INDEX branches_organization_id_primary_unique ON branches (organization_id) WHERE is_primary = true');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS branches_organization_id_primary_unique');

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['address_line_1', 'address_line_2', 'city', 'state', 'country_code']);
        });
    }
};
