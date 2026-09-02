<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 37 – Remaining Operational Tables
 *
 * Tables for modules listed in OPESCARE_MISSING_OPERATIONAL_MODULES that
 * still have no corresponding DB tables:
 *
 * Visit Flow:
 *   - visit_closures           (Module 9)
 *
 * Support / Helpdesk:
 *   - ticket_status_histories  (Module 10)
 *   - support_categories       (Module 10)
 *   - incident_escalations     (Module 10)
 *   - sla_policies             (Module 10)
 *   - support_attachments      (Module 10)
 *
 * Data Import:
 *   - import_files             (Module 11)
 *   - import_rows              (Module 11)
 *   - import_previews          (Module 11)
 *   - import_duplicate_candidates (Module 11)
 *
 * Global Search:
 *   - search_logs              (Module 14)
 *   - saved_searches           (Module 14)
 *
 * Staff / HR:
 *   - staff_credentials        (Module 15)
 *   - staff_training_statuses  (Module 15)
 *   - staff_availabilities     (Module 15)
 *
 * Triage / Emergency:
 *   - triage_scores            (Module 16)
 *   - chief_complaints         (Module 16)
 *   - emergency_cases          (Module 16)
 *   - emergency_escalations    (Module 16)
 *   - triage_reassessments     (Module 16)
 *
 * Inventory:
 *   - inventory_categories     (Module 17)
 *   - stock_adjustments        (Module 17)
 *   - stock_audits             (Module 17)
 *
 * Admin Control Center:
 *   - system_health_snapshots  (Module 12)
 *   - countries                (Module 12)
 *   - regions                  (Module 12)
 *   - language_settings        (Module 12)
 *
 * All idempotent via hasTable() guards.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Visit Closure ─────────────────────────────────────────────────────
        if (!Schema::hasTable('visit_closures')) {
            Schema::create('visit_closures', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('visit_id')->unique();
                $table->string('closed_by');
                $table->string('closure_type')->default('normal'); // normal|discharge|transfer|death|ama|cancelled
                $table->text('discharge_summary')->nullable();
                $table->text('follow_up_instructions')->nullable();
                $table->boolean('billing_settled')->default(false);
                $table->boolean('documents_complete')->default(false);
                $table->boolean('prescriptions_dispensed')->default(false);
                $table->boolean('patient_notified')->default(false);
                $table->json('blockers_resolved')->nullable();
                $table->timestamp('closed_at');
                $table->timestamps();

                $table->index('visit_id');
            });
        }

        // ── Support / Helpdesk ────────────────────────────────────────────────

        if (!Schema::hasTable('support_categories')) {
            Schema::create('support_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('context')->default('general'); // general|patient|facility|developer|partner
                $table->string('default_priority')->default('normal'); // low|normal|high|urgent|critical
                $table->boolean('requires_patient_context')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ticket_status_histories')) {
            Schema::create('ticket_status_histories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('support_ticket_id');
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->string('changed_by')->nullable();
                $table->string('reason')->nullable();
                $table->timestamp('changed_at');
                $table->timestamps();

                $table->index('support_ticket_id');
                $table->index('changed_at');
            });
        }

        if (!Schema::hasTable('incident_escalations')) {
            Schema::create('incident_escalations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('incident_report_id');
                $table->uuid('support_ticket_id')->nullable();
                $table->string('escalated_by');
                $table->string('escalated_to');          // team/role/user
                $table->string('escalation_level');      // L1|L2|L3|security|engineering|privacy|executive
                $table->text('reason');
                $table->string('status')->default('open'); // open|acknowledged|resolved
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('escalated_at');
                $table->timestamps();

                $table->index('incident_report_id');
            });
        }

        if (!Schema::hasTable('sla_policies')) {
            Schema::create('sla_policies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('applies_to')->default('support_ticket'); // support_ticket|incident
                $table->string('priority');                // low|normal|high|urgent|critical
                $table->integer('first_response_minutes');
                $table->integer('resolution_minutes');
                $table->boolean('business_hours_only')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['applies_to', 'priority', 'is_active']);
            });
        }

        if (!Schema::hasTable('support_attachments')) {
            Schema::create('support_attachments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('support_ticket_id');
                $table->uuid('ticket_message_id')->nullable();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable(); // bytes
                $table->string('uploaded_by');
                $table->string('access_level')->default('ticket_parties'); // ticket_parties|support_agents|admin
                $table->timestamps();

                $table->index('support_ticket_id');
            });
        }

        // ── Data Import ───────────────────────────────────────────────────────

        if (!Schema::hasTable('import_files')) {
            Schema::create('import_files', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('import_job_id');
                $table->string('original_name');
                $table->string('stored_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('extension')->nullable();    // csv|xlsx|xls
                $table->integer('detected_columns')->nullable();
                $table->integer('detected_rows')->nullable();
                $table->json('detected_headers')->nullable();
                $table->timestamps();

                $table->index('import_job_id');
            });
        }

        if (!Schema::hasTable('import_rows')) {
            Schema::create('import_rows', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('import_job_id');
                $table->uuid('import_batch_id')->nullable();
                $table->unsignedInteger('row_number');
                $table->json('raw_data');
                $table->json('mapped_data')->nullable();
                $table->string('status')->default('pending'); // pending|valid|invalid|imported|skipped|duplicate
                $table->string('result_model_type')->nullable(); // created model type
                $table->uuid('result_model_id')->nullable();     // created model id
                $table->timestamps();

                $table->index(['import_job_id', 'status']);
                $table->index('import_batch_id');
            });
        }

        if (!Schema::hasTable('import_previews')) {
            Schema::create('import_previews', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('import_job_id')->unique();
                $table->integer('total_rows')->default(0);
                $table->integer('valid_rows')->default(0);
                $table->integer('invalid_rows')->default(0);
                $table->integer('duplicate_candidates')->default(0);
                $table->integer('records_to_create')->default(0);
                $table->integer('records_to_update')->default(0);
                $table->boolean('approved_for_import')->default(false);
                $table->string('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->json('preview_sample')->nullable(); // first N rows for display
                $table->timestamps();

                $table->index('import_job_id');
            });
        }

        if (!Schema::hasTable('import_duplicate_candidates')) {
            Schema::create('import_duplicate_candidates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('import_job_id');
                $table->uuid('import_row_id');
                $table->string('existing_model_type');
                $table->uuid('existing_model_id');
                $table->decimal('similarity_score', 5, 4)->default(0); // 0.0 to 1.0
                $table->json('matching_fields')->nullable();
                $table->string('resolution')->default('pending'); // pending|skip|merge_candidate|create_new
                $table->string('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index('import_job_id');
                $table->index('import_row_id');
            });
        }

        // ── Global Search ─────────────────────────────────────────────────────

        if (!Schema::hasTable('search_logs')) {
            Schema::create('search_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('actor_id')->nullable();
                $table->string('actor_type')->default('user');
                $table->uuid('facility_id')->nullable();
                $table->string('query_text');
                $table->string('search_target')->nullable(); // patients|documents|facilities|medicines|all
                $table->integer('results_count')->default(0);
                $table->boolean('is_sensitive')->default(false); // patient/Health ID searches
                $table->string('ip_address')->nullable();
                $table->timestamp('searched_at');
                $table->timestamps();

                $table->index(['actor_id', 'searched_at']);
                $table->index('is_sensitive');
            });
        }

        if (!Schema::hasTable('saved_searches')) {
            Schema::create('saved_searches', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('user_id');
                $table->string('name');
                $table->string('search_target');
                $table->json('filters');
                $table->integer('use_count')->default(0);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
            });
        }

        // ── Staff / HR ────────────────────────────────────────────────────────

        if (!Schema::hasTable('staff_credentials')) {
            Schema::create('staff_credentials', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('staff_profile_id');
                $table->string('credential_type');      // degree|certification|registration|award
                $table->string('title');
                $table->string('issuing_body')->nullable();
                $table->string('credential_number')->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('document_path')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->string('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index('staff_profile_id');
                $table->index('expiry_date');
            });
        }

        if (!Schema::hasTable('staff_training_statuses')) {
            Schema::create('staff_training_statuses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('staff_profile_id');
                $table->string('training_type');        // module|certification|onboarding|privacy|clinical
                $table->string('training_name');
                $table->string('training_reference')->nullable(); // Academy certification code or external
                $table->string('status')->default('pending'); // pending|in_progress|completed|expired
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('completed_by_source')->nullable(); // academy|external|self_declared
                $table->timestamps();

                $table->index(['staff_profile_id', 'status']);
            });
        }

        if (!Schema::hasTable('staff_availabilities')) {
            Schema::create('staff_availabilities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('staff_profile_id');
                $table->uuid('facility_id')->nullable();
                $table->string('day_of_week')->nullable(); // monday|tuesday|...|sunday|all
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->boolean('is_on_call')->default(false);
                $table->boolean('is_available')->default(true);
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['staff_profile_id', 'day_of_week']);
            });
        }

        // ── Triage / Emergency ────────────────────────────────────────────────

        if (!Schema::hasTable('triage_scores')) {
            Schema::create('triage_scores', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('triage_assessment_id');
                $table->uuid('visit_id');
                $table->string('scoring_system')->default('manual'); // manual|manchester|esi|sats|custom
                $table->string('priority_level');       // P1_immediate|P2_urgent|P3_less_urgent|P4_standard|P5_non_urgent
                $table->integer('numeric_score')->nullable();
                $table->json('component_scores')->nullable(); // sub-scores by category
                $table->string('computed_by')->nullable();    // nurse/system
                $table->timestamp('scored_at');
                $table->timestamps();

                $table->index('triage_assessment_id');
                $table->index('visit_id');
            });
        }

        if (!Schema::hasTable('chief_complaints')) {
            Schema::create('chief_complaints', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('triage_assessment_id');
                $table->uuid('patient_id');
                $table->uuid('visit_id');
                $table->text('complaint_text');
                $table->string('complaint_category')->nullable(); // respiratory|cardiac|neurological|gastrointestinal|trauma|pain|other
                $table->integer('pain_score')->nullable();         // 0-10
                $table->integer('duration_hours')->nullable();
                $table->json('associated_symptoms')->nullable();
                $table->string('recorded_by');
                $table->timestamp('recorded_at');
                $table->timestamps();

                $table->index('triage_assessment_id');
                $table->index('patient_id');
            });
        }

        if (!Schema::hasTable('emergency_cases')) {
            Schema::create('emergency_cases', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('visit_id');
                $table->uuid('patient_id');
                $table->uuid('facility_id');
                $table->string('emergency_type');       // cardiac_arrest|trauma|respiratory|poisoning|obstetric|other
                $table->string('severity')->default('critical'); // critical|life_threatening|urgent
                $table->string('status')->default('active'); // active|stabilized|transferred|deceased|resolved
                $table->string('response_lead')->nullable();    // assigned doctor/team
                $table->text('emergency_notes')->nullable();
                $table->timestamp('declared_at');
                $table->timestamp('stabilized_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index('visit_id');
                $table->index(['facility_id', 'status']);
            });
        }

        if (!Schema::hasTable('emergency_escalations')) {
            Schema::create('emergency_escalations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('emergency_case_id');
                $table->uuid('visit_id');
                $table->string('escalated_by');
                $table->string('escalation_reason');
                $table->string('escalation_type');      // internal_alert|specialist_call|referral|transfer
                $table->string('target');                // team/role/facility
                $table->string('status')->default('pending'); // pending|acknowledged|responded
                $table->timestamp('acknowledged_at')->nullable();
                $table->timestamp('escalated_at');
                $table->timestamps();

                $table->index('emergency_case_id');
                $table->index('visit_id');
            });
        }

        if (!Schema::hasTable('triage_reassessments')) {
            Schema::create('triage_reassessments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('triage_assessment_id');
                $table->uuid('visit_id');
                $table->string('reassessed_by');
                $table->string('previous_priority');
                $table->string('new_priority');
                $table->text('reason_for_change');
                $table->json('new_vital_signs')->nullable();
                $table->timestamp('reassessed_at');
                $table->timestamps();

                $table->index('triage_assessment_id');
                $table->index('visit_id');
            });
        }

        // ── Inventory ─────────────────────────────────────────────────────────

        if (!Schema::hasTable('inventory_categories')) {
            Schema::create('inventory_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->uuid('parent_id')->nullable(); // sub-categories
                $table->string('item_type')->default('consumable'); // consumable|equipment|medicine|reagent
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->index(['parent_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('stock_adjustments')) {
            Schema::create('stock_adjustments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('inventory_item_id');
                $table->uuid('stock_batch_id')->nullable();
                $table->uuid('stock_location_id')->nullable();
                $table->uuid('facility_id');
                $table->string('adjustment_type');       // physical_count|damage|expiry|theft|correction|write_off
                $table->decimal('quantity_before', 12, 3);
                $table->decimal('quantity_adjusted', 12, 3); // can be negative
                $table->decimal('quantity_after', 12, 3);
                $table->string('unit')->default('each');
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->string('adjusted_by');
                $table->string('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->boolean('requires_approval')->default(false);
                $table->timestamps();

                $table->index('inventory_item_id');
                $table->index('facility_id');
            });
        }

        if (!Schema::hasTable('stock_audits')) {
            Schema::create('stock_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->uuid('stock_location_id')->nullable();
                $table->string('audit_type')->default('full'); // full|partial|spot_check
                $table->string('status')->default('planned'); // planned|in_progress|completed|cancelled
                $table->string('initiated_by');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->integer('items_counted')->default(0);
                $table->integer('discrepancies_found')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['facility_id', 'status']);
            });
        }

        // ── Admin Control Center ──────────────────────────────────────────────

        if (!Schema::hasTable('system_health_snapshots')) {
            Schema::create('system_health_snapshots', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('status')->default('healthy'); // healthy|degraded|critical|maintenance
                $table->json('checks');  // {db: 'ok', queue: 'ok', storage: 'ok', ...}
                $table->integer('failed_jobs_count')->default(0);
                $table->integer('webhook_failures_24h')->default(0);
                $table->integer('api_error_rate_pct')->default(0);
                $table->decimal('disk_used_pct', 5, 2)->default(0);
                $table->decimal('avg_response_ms', 10, 2)->nullable();
                $table->timestamp('snapshot_at');
                $table->timestamps();

                $table->index('snapshot_at');
                $table->index('status');
            });
        }

        if (!Schema::hasTable('countries')) {
            Schema::create('countries', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('iso2', 2)->unique();
                $table->string('iso3', 3)->unique()->nullable();
                $table->string('phone_code', 10)->nullable();
                $table->string('currency_code', 3)->nullable();
                $table->string('timezone')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('regions')) {
            Schema::create('regions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('country_id');
                $table->string('name');
                $table->string('code')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('country_id');
                $table->index(['country_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('language_settings')) {
            Schema::create('language_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 10)->unique(); // en|fr|ar|pt|sw etc
                $table->string('name');
                $table->string('native_name')->nullable();
                $table->string('direction')->default('ltr'); // ltr|rtl
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_required')->default(false); // cannot be disabled (en, fr)
                $table->integer('display_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Reverse order
        $tables = [
            'language_settings', 'regions', 'countries', 'system_health_snapshots',
            'stock_audits', 'stock_adjustments', 'inventory_categories',
            'triage_reassessments', 'emergency_escalations', 'emergency_cases',
            'chief_complaints', 'triage_scores',
            'staff_availabilities', 'staff_training_statuses', 'staff_credentials',
            'saved_searches', 'search_logs',
            'import_duplicate_candidates', 'import_previews', 'import_rows', 'import_files',
            'support_attachments', 'sla_policies', 'incident_escalations',
            'ticket_status_histories', 'support_categories',
            'visit_closures',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
