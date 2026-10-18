<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Facility documents (pharmacy reports, the controlled-substance register)
 * carry the same /verify/doc QR as patient documents. They have no patient,
 * so `patient_id` becomes nullable and the issuing facility is recorded
 * instead. Patient documents are unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_verifications', function (Blueprint $table) {
            if (! Schema::hasColumn('document_verifications', 'facility_id')) {
                $table->uuid('facility_id')->nullable()->index()->after('patient_id');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE document_verifications ALTER COLUMN patient_id DROP NOT NULL');
        } else {
            Schema::table('document_verifications', function (Blueprint $table) {
                $table->uuid('patient_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Facility rows cannot satisfy NOT NULL; remove them before restoring it.
        DB::table('document_verifications')->whereNull('patient_id')->delete();

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE document_verifications ALTER COLUMN patient_id SET NOT NULL');
        }

        Schema::table('document_verifications', function (Blueprint $table) {
            if (Schema::hasColumn('document_verifications', 'facility_id')) {
                $table->dropColumn('facility_id');
            }
        });
    }
};
