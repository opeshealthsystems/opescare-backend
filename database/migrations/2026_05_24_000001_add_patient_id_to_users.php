<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('patient_id')->nullable()->after('primary_facility_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            // is_demo already exists (added by 2026_05_17_173408_add_demo_isolation_fields_to_tables)
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn(['patient_id']);
        });
    }
};
