<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An order raised from a PATIENT'S OWN REQUEST (lab_test_requests → approve).
 *
 * Until now approving a request stamped `ordered_by` with the laboratory
 * member who approved it, so the release bell and the results-inbox row went
 * to a lab account whose app hides the results tab — nobody acknowledged the
 * result. A patient-requested order has no ordering clinician: `ordered_by`
 * is null and this flag says why, and the inbox routes the released result to
 * the patient's care team instead (LabDeskService::acknowledgersFor).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_orders') || Schema::hasColumn('lab_orders', 'requested_by_patient')) {
            return;
        }

        Schema::table('lab_orders', function (Blueprint $table) {
            $table->boolean('requested_by_patient')->default(false)->after('ordered_by');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('lab_orders') && Schema::hasColumn('lab_orders', 'requested_by_patient')) {
            Schema::table('lab_orders', function (Blueprint $table) {
                $table->dropColumn('requested_by_patient');
            });
        }
    }
};
