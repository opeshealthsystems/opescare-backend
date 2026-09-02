<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 43 — Gap-Fill Models
 *
 * Adds five models identified as missing during final v3_full_flows.md audit:
 *
 *   - external_record_matches    (Module 37 — Data Quality & Reconciliation)
 *   - data_completeness_scores   (Module 37 — Data Quality & Reconciliation)
 *   - search_indices             (Module 38 — Global Search)
 *   - attachment_access_logs     (Module 39 — File Storage & Medical Attachments)
 *   - facility_readiness_scores  (Module 40 — Facility Go-Live Readiness)
 *
 * All tables are guarded with Schema::hasTable() for idempotency.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Module 37 ─────────────────────────────────────────────────────────

        if (! Schema::hasTable('external_record_matches')) {
            Schema::create('external_record_matches', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('reconciliation_case_id')->nullable()->index();
                $table->string('external_system');           // e.g. FHIR|HL7|CSV|DHIS2
                $table->string('external_record_id');
                $table->string('external_record_type');      // Patient|Encounter|Observation|etc
                $table->uuid('matched_patient_id')->nullable();
                $table->string('match_status');              // unmatched|matched|rejected|manual
                $table->float('match_confidence')->nullable(); // 0.0 – 1.0
                $table->json('match_fields')->nullable();    // fields used for matching
                $table->text('notes')->nullable();
                $table->uuid('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['external_system', 'external_record_id'], 'erm_external_idx');
                $table->index(['match_status'], 'erm_status_idx');
            });
        }

        if (! Schema::hasTable('data_completeness_scores')) {
            Schema::create('data_completeness_scores', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('resource_type');             // Patient|Facility|Encounter|etc
                $table->uuid('resource_id');
                $table->float('score');                      // 0.0 – 100.0
                $table->json('missing_fields')->nullable();  // array of field names that are empty
                $table->json('present_fields')->nullable();  // array of field names that are filled
                $table->integer('total_fields')->default(0);
                $table->integer('filled_fields')->default(0);
                $table->timestamp('calculated_at');
                $table->timestamps();

                $table->index(['resource_type', 'resource_id'], 'dcs_resource_idx');
                $table->index('score', 'dcs_score_idx');
            });
        }

        // ── Module 38 ─────────────────────────────────────────────────────────

        if (! Schema::hasTable('search_indices')) {
            Schema::create('search_indices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('resource_type');             // Patient|Facility|Medicine|Lab|etc
                $table->uuid('resource_id');
                $table->uuid('facility_id')->nullable();     // scope for permission filtering
                $table->uuid('organization_id')->nullable();
                $table->text('search_text');                 // denormalized searchable content
                $table->json('metadata')->nullable();        // extra facets for filtering
                $table->boolean('is_active')->default(true);
                $table->timestamp('indexed_at');
                $table->timestamps();

                $table->index(['resource_type', 'resource_id'], 'si_resource_idx');
                $table->index(['facility_id', 'resource_type'], 'si_facility_type_idx');
                $table->index('is_active', 'si_active_idx');
            });
        }

        // ── Module 39 ─────────────────────────────────────────────────────────

        if (! Schema::hasTable('attachment_access_logs')) {
            Schema::create('attachment_access_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_asset_id');
                $table->uuid('accessed_by');
                $table->string('access_type');               // view|download|preview|share
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('access_reason')->nullable(); // clinical|insurance|admin|audit
                $table->uuid('facility_id')->nullable();
                $table->timestamps();

                $table->index(['file_asset_id'], 'aal_asset_idx');
                $table->index(['accessed_by'], 'aal_user_idx');
                $table->index(['created_at'], 'aal_created_idx');
            });
        }

        // ── Module 40 ─────────────────────────────────────────────────────────

        if (! Schema::hasTable('facility_readiness_scores')) {
            Schema::create('facility_readiness_scores', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->uuid('go_live_checklist_id')->nullable();
                $table->float('overall_score');              // 0.0 – 100.0
                $table->float('staff_score')->nullable();    // roles/training
                $table->float('config_score')->nullable();   // departments/services/templates
                $table->float('data_score')->nullable();     // imports/data readiness
                $table->float('support_score')->nullable();  // support/escalation readiness
                $table->json('open_blockers')->nullable();   // array of blocker descriptions
                $table->json('recommendations')->nullable(); // suggested actions
                $table->boolean('is_ready')->default(false); // overall gate pass/fail
                $table->timestamp('calculated_at');
                $table->timestamps();

                $table->index('facility_id', 'frs_facility_idx');
                $table->index('is_ready', 'frs_ready_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_readiness_scores');
        Schema::dropIfExists('attachment_access_logs');
        Schema::dropIfExists('search_indices');
        Schema::dropIfExists('data_completeness_scores');
        Schema::dropIfExists('external_record_matches');
    }
};
