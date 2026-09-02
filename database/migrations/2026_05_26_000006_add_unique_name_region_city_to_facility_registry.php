<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_registry', function (Blueprint $table) {
            $table->unique(['name', 'region', 'city'], 'uq_fr_name_region_city');
        });
    }

    public function down(): void
    {
        Schema::table('facility_registry', function (Blueprint $table) {
            $table->dropUnique('uq_fr_name_region_city');
        });
    }
};
