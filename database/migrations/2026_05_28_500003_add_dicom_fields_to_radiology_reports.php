<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('radiology_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('radiology_reports', 'dicom_study_id')) {
                $table->foreignUuid('dicom_study_id')->nullable()->constrained('dicom_studies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('radiology_reports', function (Blueprint $table) {
            if (Schema::hasColumn('radiology_reports', 'dicom_study_id')) {
                $table->dropForeign(['dicom_study_id']);
                $table->dropColumn('dicom_study_id');
            }
        });
    }
};
