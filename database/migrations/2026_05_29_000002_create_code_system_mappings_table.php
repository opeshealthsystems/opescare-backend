<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Code System Mappings Table
 *
 * Stores mappings between OpesCare local codes and standard terminologies:
 * - LOINC  — lab tests, observations
 * - ICD-10 — diagnoses
 * - ATC    — medications (Anatomical Therapeutic Chemical)
 * - SNOMED — clinical concepts (future)
 *
 * Used by: FHIR layer, public health reporting, interoperability.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_system_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Local (OpesCare) side
            $table->string('local_code', 100);          // facility/system code
            $table->string('local_name', 300)->nullable();
            $table->string('local_unit', 100)->nullable(); // for lab tests: mmol/L, g/dL etc.
            $table->string('resource_type', 50);         // LabTest|Diagnosis|Medication|Observation

            // Standard (external) side
            $table->string('standard_system', 30);       // loinc|icd10|atc|snomed|cpt
            $table->string('standard_code', 100);        // e.g. '718-7', 'A09', 'J01CA01'
            $table->string('standard_display', 300)->nullable(); // official display name
            $table->string('standard_version', 20)->nullable();  // e.g. '2.77', '11th Rev'

            // Mapping quality
            $table->string('mapping_confidence', 20)->default('manual'); // manual|exact|broader|narrower|approximate
            $table->decimal('confidence_score', 4, 2)->nullable();       // 0.00-1.00 if ML-assisted

            // Review / approval
            $table->string('status', 20)->default('pending');  // pending|approved|rejected|deprecated
            $table->string('approved_by', 100)->nullable();    // user id or name
            $table->timestamp('approved_at')->nullable();
            $table->string('notes', 500)->nullable();

            // Facility scope (null = platform-wide)
            $table->uuid('facility_id')->nullable();

            // Audit
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Uniqueness: one mapping per local_code + standard_system per facility
            $table->unique(['local_code', 'standard_system', 'facility_id'], 'code_system_mappings_unique');

            $table->index(['standard_system', 'status']);
            $table->index(['resource_type', 'standard_system']);
            $table->index('local_code');
            $table->index('standard_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_system_mappings');
    }
};
