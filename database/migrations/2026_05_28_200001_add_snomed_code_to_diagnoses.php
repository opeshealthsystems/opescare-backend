<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->string('snomed_code', 30)->nullable()->after('code')
                ->comment('SNOMED CT concept ID (e.g. 73211009 for Diabetes mellitus)');
            $table->string('snomed_display', 255)->nullable()->after('snomed_code')
                ->comment('SNOMED CT preferred term');
        });
    }

    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropColumn(['snomed_code', 'snomed_display']);
        });
    }
};
