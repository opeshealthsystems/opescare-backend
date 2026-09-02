<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_results', 'loinc_code')) {
                $table->string('loinc_code', 30)->nullable();
                $table->string('loinc_display')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            if (Schema::hasColumn('lab_results', 'loinc_code')) {
                $table->dropColumn(['loinc_code', 'loinc_display']);
            }
        });
    }
};
