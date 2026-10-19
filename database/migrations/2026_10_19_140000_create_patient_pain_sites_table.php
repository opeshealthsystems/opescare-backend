<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where it hurts — one row per reported pain site.
 *
 * Captured at three doors (App\Enums\PainSiteSource): the patient's own
 * pre-consult check-in, the triage desk, and a clinician working from the
 * triage record. The chart READS these; it never writes them (routes/chart.php
 * is read-only by design).
 *
 * `region` is a closed vocabulary (App\Enums\PainRegion) so a marker can be
 * placed on the body map; free text belongs in `note`. A site is never
 * deleted — it is resolved (`resolved_at`), so the record keeps the history.
 * `facility_id` is the facility the report was made TO; the chart shows only
 * the active facility's sites, as it does for pre-consult answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_pain_sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('encounter_id')->nullable()->index();
            $table->uuid('triage_record_id')->nullable()->index();
            $table->uuid('appointment_id')->nullable()->index();
            $table->uuid('facility_id')->index();
            $table->string('region', 32);
            $table->unsignedTinyInteger('severity');
            $table->timestampTz('onset_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('trend', 16)->nullable();
            $table->uuid('recorded_by_user_id')->nullable()->index();
            $table->string('source', 24);
            $table->timestampTz('resolved_at')->nullable();
            $table->uuid('resolved_by_user_id')->nullable();
            $table->boolean('is_demo')->default(false)->index();
            $table->timestampsTz();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('recorded_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['patient_id', 'facility_id', 'resolved_at']);
        });

        \Illuminate\Support\Facades\DB::statement('ALTER TABLE patient_pain_sites ADD CONSTRAINT patient_pain_sites_severity_range CHECK (severity BETWEEN 0 AND 10)');
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_pain_sites');
    }
};
