<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 42 — FHIR Supplement Tables
 *
 * Adds:
 *   - fhir_resource_references: tracks external FHIR resource IDs for OpesCare records
 *   - external_identifiers: generic cross-system identifier store
 *
 * Both are idempotent (Schema::hasTable guards).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fhir_resource_references')) {
            Schema::create('fhir_resource_references', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('internal_resource_type');    // Patient|Encounter|Observation|etc
                $table->uuid('internal_record_id');
                $table->string('fhir_resource_type');        // FHIR R4 resource type
                $table->string('fhir_resource_id');          // External FHIR server resource ID
                $table->string('fhir_server_url')->nullable();
                $table->string('fhir_version')->default('R4');
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(['internal_resource_type', 'internal_record_id', 'fhir_resource_type'], 'fhir_refs_unique');
                $table->index(['internal_resource_type', 'internal_record_id'], 'fhir_refs_internal_idx');
            });
        }

        if (! Schema::hasTable('external_identifiers')) {
            Schema::create('external_identifiers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('resource_type');             // Patient|Facility|Practitioner|Organization|etc
                $table->uuid('resource_id');
                $table->string('system');                    // e.g. http://hl7.org/fhir/sid/us-npi | custom URN
                $table->string('value');                     // the identifier value
                $table->string('use')->nullable();           // official|temp|secondary|old (FHIR Identifier.use)
                $table->string('type')->nullable();          // MR|NI|PRN|etc
                $table->timestamp('period_start')->nullable();
                $table->timestamp('period_end')->nullable();
                $table->timestamps();

                $table->index(['resource_type', 'resource_id']);
                $table->index(['system', 'value']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('external_identifiers');
        Schema::dropIfExists('fhir_resource_references');
    }
};
