<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('critical_value_alerts') || !Schema::hasTable('lab_results')) {
            return;
        }

        Schema::table('critical_value_alerts', function (Blueprint $table) {
            $table->foreign('lab_result_id')->references('id')->on('lab_results')->cascadeOnDelete();
        });

        if (Schema::hasTable('dicom_studies') && Schema::hasTable('lab_orders')) {
            Schema::table('dicom_studies', function (Blueprint $table) {
                $table->foreign('lab_order_id')->references('id')->on('lab_orders')->nullOnDelete();
            });
        }

        if (Schema::hasTable('controlled_substance_records') && Schema::hasTable('prescriptions')) {
            Schema::table('controlled_substance_records', function (Blueprint $table) {
                $table->foreign('prescription_id')->references('id')->on('prescriptions')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('critical_value_alerts', function (Blueprint $table) {
            $table->dropForeign(['lab_result_id']);
        });

        if (Schema::hasTable('dicom_studies')) {
            Schema::table('dicom_studies', function (Blueprint $table) {
                $table->dropForeign(['lab_order_id']);
            });
        }
    }
};
