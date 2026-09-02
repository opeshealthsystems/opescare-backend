<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storage for postnatal care visits.
 *
 * MaternityController::recordPostnatalVisit() has existed, fully written —
 * facility guard, complete validation, PNC certificate issuance — since the
 * Maternity module landed. What never landed was anywhere to put the data:
 * it called MaternityService::recordPostnatalVisit(), which did not exist, so
 * POST /v1/clinical/postnatal-visits threw BadMethodCallException on every
 * request. No test covered it, so the suite stayed green over a dead endpoint.
 *
 * Columns mirror the controller's own validation rules, which are the closest
 * thing to a specification this feature has, and follow the antenatal_visits
 * conventions (UUID keys, soft deletes, smallint for clinical integers).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postnatal_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('patient_id');
            $table->uuid('facility_id');
            $table->uuid('provider_id');

            $table->date('visit_date');
            // 0-365, per the controller's validation.
            $table->smallInteger('days_postpartum');

            $table->smallInteger('bp_systolic')->nullable();
            $table->smallInteger('bp_diastolic')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();

            // Free-text-with-a-known-vocabulary, matching how antenatal_visits
            // stores presentation/oedema: the controller validates the allowed
            // values, the column stays a plain string.
            $table->string('lochia')->nullable();               // rubra|serosa|alba|none
            $table->string('wound_healing')->nullable();        // normal|delayed|infected|dehisced
            $table->string('breastfeeding_status')->nullable(); // exclusive|partial|none

            $table->integer('infant_weight_grams')->nullable();
            $table->text('notes')->nullable();
            $table->string('next_visit_plan', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities');
            $table->foreign('provider_id')->references('id')->on('users');

            // The two reads this table gets: a patient's postnatal history, and
            // a facility's postnatal workload for a period.
            $table->index(['patient_id', 'visit_date']);
            $table->index(['facility_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postnatal_visits');
    }
};
