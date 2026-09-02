<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX (audit 2026-06-18): Add patient_id column to clinical_notes table.
     *
     * The original migration (2026_05_15_001327) omitted patient_id from
     * clinical_notes, even though the Data Dictionary defines it as a required field.
     * While patient_id can be derived via visit->patient_id, direct FK queries
     * and cascade operations are unable to trace back to the patient when the
     * visit record is deleted.
     *
     * This migration:
     *   1. Adds patient_id (nullable, FK to patients, SET NULL on delete)
     *   2. Backfills patient_id from visits table for existing records
     */
    public function up(): void
    {
        Schema::table('clinical_notes', function (Blueprint $table) {
            $table->uuid('patient_id')->nullable()->index()->after('visit_id');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
        });

        // Backfill patient_id from visits for existing records
        if (config('database.default') === 'pgsql') {
            DB::statement('
                UPDATE clinical_notes cn
                SET patient_id = v.patient_id
                FROM visits v
                WHERE cn.visit_id = v.id
                AND cn.patient_id IS NULL
            ');
        } else {
            // SQLite / MySQL fallback
            DB::statement('
                UPDATE clinical_notes cn
                INNER JOIN visits v ON cn.visit_id = v.id
                SET cn.patient_id = v.patient_id
                WHERE cn.patient_id IS NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('clinical_notes', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });
    }
};
