<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records a patient brought in themselves from another provider.
 *
 * Deliberately a table of its own, not rows in observations / immunization
 * records / clinical tables: those are clinician-attested, facility-scoped and
 * read by every clinical surface as fact. A patient-provided item is
 * provenance 'patient_import', verification 'unverified', and stays that way
 * until a clinician re-records it in their own chart. Nothing here is ever
 * read by the finder's availability/stock queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_imported_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('import_batch_id')->index();
            $table->string('resource_type', 40);          // FHIR type, or 'Document' for an uploaded file
            $table->string('provenance', 30)->default('patient_import');
            $table->string('verification_status', 20)->default('unverified');
            $table->string('original_source_system', 255)->nullable();
            $table->string('original_source_name', 255)->nullable();
            $table->string('external_id', 255)->nullable();
            $table->string('dedupe_hash', 64);
            $table->string('title', 500)->nullable();
            $table->string('code_system', 255)->nullable();
            $table->string('code', 100)->nullable();
            $table->string('value_text', 500)->nullable();
            $table->string('status', 40)->nullable();
            $table->timestamp('effective_at')->nullable();
            $table->text('payload')->nullable();            // encrypted JSON of the source resource
            $table->string('file_path', 500)->nullable();
            $table->string('file_mime', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'dedupe_hash']);
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_imported_records');
    }
};
