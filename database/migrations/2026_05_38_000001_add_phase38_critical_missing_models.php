<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 38 — Critical Missing Models Migration
 *
 * Adds tables for:
 *   - Encounter (core clinical)
 *   - Telemedicine (5 tables)
 *   - Ward supplements: inpatient_notes, nursing_rounds, discharge_plans
 *   - CDSS supplement: dose_warning_rules
 *   - Security/Compliance: access_reviews, compliance_cases
 *   - FHIR interoperability: fhir_mappings, fhir_mapping_versions, fhir_mapping_fields, mapping_errors
 *   - Research Access Program (7 tables)
 *   - Country Expansion Framework (7 tables)
 *   - Subscription: plan_limits
 *   - Import: import_templates
 *
 * All tables are idempotent (Schema::hasTable guards).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Encounter ──────────────────────────────────────────────────────
        if (! Schema::hasTable('encounters')) {
            Schema::create('encounters', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('visit_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('facility_id')->constrained('care_facilities')->cascadeOnDelete();
                $table->string('encounter_type');          // outpatient|inpatient|emergency|telemedicine|referral
                $table->string('status')->default('active'); // active|completed|cancelled
                $table->string('encounter_class')->nullable(); // AMB|IMP|EMER|VR (FHIR class codes)
                $table->uuid('attending_provider_id')->nullable();
                $table->uuid('admission_id')->nullable();
                $table->text('reason_for_encounter')->nullable();
                $table->text('discharge_disposition')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();
            });
        }

        // ── 2. Telemedicine ───────────────────────────────────────────────────
        if (! Schema::hasTable('teleconsultations')) {
            Schema::create('teleconsultations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('visit_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('facility_id')->constrained('care_facilities')->cascadeOnDelete();
                $table->uuid('provider_id');
                $table->string('status')->default('scheduled'); // scheduled|waiting|active|completed|cancelled|failed
                $table->string('platform')->nullable();          // own|zoom|meet|teams
                $table->string('session_url')->nullable();
                $table->string('session_token')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->string('cancellation_reason')->nullable();
                $table->text('technical_notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('telemedicine_consents')) {
            Schema::create('telemedicine_consents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('teleconsultation_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
                $table->boolean('consented')->default(false);
                $table->string('consent_method')->nullable();    // verbal|digital|written
                $table->text('consent_text_version')->nullable();
                $table->uuid('witnessed_by')->nullable();
                $table->timestamp('consented_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('virtual_waiting_rooms')) {
            Schema::create('virtual_waiting_rooms', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('facility_id')->constrained('care_facilities')->cascadeOnDelete();
                $table->foreignUuid('teleconsultation_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('waiting'); // waiting|called|joined|left|expired
                $table->timestamp('joined_at')->nullable();
                $table->timestamp('called_at')->nullable();
                $table->integer('estimated_wait_minutes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('call_sessions')) {
            Schema::create('call_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('teleconsultation_id')->constrained()->cascadeOnDelete();
                $table->string('session_provider');             // webrtc|zoom|meet|teams
                $table->string('external_session_id')->nullable();
                $table->string('status')->default('initiated'); // initiated|active|ended|failed
                $table->boolean('video_enabled')->default(true);
                $table->boolean('audio_enabled')->default(true);
                $table->boolean('recording_enabled')->default(false);
                $table->string('recording_url')->nullable();
                $table->integer('participant_count')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->json('connection_quality_log')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('telemedicine_notes')) {
            Schema::create('telemedicine_notes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('teleconsultation_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
                $table->uuid('authored_by');
                $table->string('note_type')->default('consultation'); // consultation|follow_up|prescription_note|referral_note
                $table->text('subjective')->nullable();
                $table->text('objective')->nullable();
                $table->text('assessment')->nullable();
                $table->text('plan')->nullable();
                $table->text('recommendations')->nullable();
                $table->boolean('is_signed')->default(false);
                $table->timestamp('signed_at')->nullable();
                $table->timestamps();
            });
        }

        // ── 3. Ward Supplements ───────────────────────────────────────────────
        if (! Schema::hasTable('inpatient_notes')) {
            Schema::create('inpatient_notes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('admission_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
                $table->uuid('authored_by');
                $table->string('note_type');  // progress|nursing|physician|specialist|discharge_summary
                $table->text('content');
                $table->boolean('is_signed')->default(false);
                $table->timestamp('signed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nursing_rounds')) {
            Schema::create('nursing_rounds', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('admission_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
                $table->uuid('nurse_id');
                $table->timestamp('round_time');
                $table->json('vital_signs')->nullable();         // BP, temp, pulse, SpO2 etc.
                $table->string('pain_level')->nullable();        // 0-10
                $table->text('observations')->nullable();
                $table->text('interventions')->nullable();
                $table->string('patient_response')->nullable();  // stable|improving|deteriorating|critical
                $table->boolean('escalation_required')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('discharge_plans')) {
            Schema::create('discharge_plans', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('admission_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
                $table->uuid('planned_by');
                $table->date('target_discharge_date')->nullable();
                $table->string('discharge_disposition')->nullable(); // home|rehab|ltc|another_facility|expired
                $table->text('discharge_criteria')->nullable();
                $table->text('follow_up_plan')->nullable();
                $table->text('medication_reconciliation_notes')->nullable();
                $table->text('patient_education_notes')->nullable();
                $table->boolean('social_support_assessed')->default(false);
                $table->boolean('transport_arranged')->default(false);
                $table->string('status')->default('draft');      // draft|ready|approved|completed|cancelled
                $table->uuid('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        // ── 4. CDSS Supplement: DoseWarningRule ───────────────────────────────
        if (! Schema::hasTable('dose_warning_rules')) {
            Schema::create('dose_warning_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('medicine_code');
                $table->string('medicine_name');
                $table->string('warning_type');       // max_single_dose|max_daily_dose|weight_based|age_based|renal_adjusted
                $table->decimal('max_dose_value', 12, 4)->nullable();
                $table->string('dose_unit')->nullable();
                $table->string('patient_population')->nullable(); // adult|paediatric|elderly|renal_impaired|hepatic_impaired
                $table->text('warning_message');
                $table->string('severity')->default('warning'); // info|warning|critical
                $table->boolean('requires_override_reason')->default(true);
                $table->boolean('is_active')->default(true);
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                // CDSS Safety note: this rule assists but does NOT replace clinical judgment.
                $table->index(['medicine_code', 'is_active']);
            });
        }

        // ── 5. Security / Compliance ──────────────────────────────────────────
        if (! Schema::hasTable('access_reviews')) {
            Schema::create('access_reviews', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('facility_id')->nullable()->constrained('care_facilities')->nullOnDelete();
                $table->string('review_scope');          // facility|role|user|module
                $table->string('reviewed_subject_type'); // role|user|permission_group
                $table->uuid('reviewed_subject_id');
                $table->uuid('reviewed_by');
                $table->string('status')->default('pending'); // pending|in_progress|completed|escalated
                $table->text('findings')->nullable();
                $table->text('recommendations')->nullable();
                $table->string('outcome')->nullable();   // no_change|modified|revoked|escalated
                $table->timestamp('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('compliance_cases')) {
            Schema::create('compliance_cases', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('facility_id')->nullable()->constrained('care_facilities')->nullOnDelete();
                $table->string('case_type');             // data_breach|policy_violation|audit_finding|regulatory|patient_complaint
                $table->string('severity');              // low|medium|high|critical
                $table->string('status')->default('open'); // open|under_review|remediation|closed|escalated
                $table->text('description');
                $table->uuid('reported_by')->nullable();
                $table->uuid('assigned_to')->nullable();
                $table->uuid('closed_by')->nullable();
                $table->text('remediation_plan')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamp('due_date')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }

        // ── 6. FHIR Interoperability ──────────────────────────────────────────
        if (! Schema::hasTable('fhir_mappings')) {
            Schema::create('fhir_mappings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('internal_resource');     // Patient|Encounter|Observation|MedicationRequest|etc
                $table->string('fhir_resource_type');    // Patient|Encounter|Observation|etc (FHIR R4)
                $table->string('fhir_version')->default('R4');
                $table->string('direction');             // outbound|inbound|bidirectional
                $table->string('status')->default('draft'); // draft|under_review|approved|deprecated
                $table->text('description')->nullable();
                $table->uuid('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->unique(['internal_resource', 'fhir_resource_type', 'fhir_version', 'direction']);
            });
        }

        if (! Schema::hasTable('fhir_mapping_versions')) {
            Schema::create('fhir_mapping_versions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('fhir_mapping_id')->constrained()->cascadeOnDelete();
                $table->integer('version_number');
                $table->string('change_summary')->nullable();
                $table->uuid('created_by')->nullable();
                $table->boolean('is_current')->default(false);
                $table->json('snapshot')->nullable();    // full field map snapshot at version time
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fhir_mapping_fields')) {
            Schema::create('fhir_mapping_fields', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('fhir_mapping_id')->constrained()->cascadeOnDelete();
                $table->string('internal_field');        // e.g. patient.first_name
                $table->string('fhir_path');             // e.g. Patient.name[0].given[0]
                $table->string('transformation')->nullable(); // none|concat|split|lookup|calculate
                $table->string('transformation_rule')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_identifier')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mapping_errors')) {
            Schema::create('mapping_errors', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('fhir_mapping_id')->nullable()->constrained()->nullOnDelete();
                $table->string('source_resource_type');
                $table->uuid('source_record_id')->nullable();
                $table->string('error_type');            // missing_field|invalid_code|transformation_failed|validation_failed
                $table->text('error_message');
                $table->json('error_context')->nullable();
                $table->string('direction');             // outbound|inbound
                $table->boolean('resolved')->default(false);
                $table->uuid('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['source_resource_type', 'resolved']);
            });
        }

        // ── 7. Research Access Program ────────────────────────────────────────
        if (! Schema::hasTable('researcher_profiles')) {
            Schema::create('researcher_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id')->nullable();
                $table->string('full_name');
                $table->string('email')->unique();
                $table->string('institution');
                $table->string('department')->nullable();
                $table->string('position')->nullable();
                $table->string('orcid_id')->nullable();
                $table->string('status')->default('pending'); // pending|active|suspended
                $table->text('research_interests')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ethics_approvals')) {
            Schema::create('ethics_approvals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('researcher_profile_id')->constrained()->cascadeOnDelete();
                $table->string('approval_reference')->unique();
                $table->string('approving_body');
                $table->string('study_title');
                $table->date('approval_date');
                $table->date('expiry_date')->nullable();
                $table->string('document_path')->nullable();
                $table->boolean('verified')->default(false);
                $table->uuid('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('data_access_committee_reviews')) {
            Schema::create('data_access_committee_reviews', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('research_access_request_id')->constrained()->cascadeOnDelete();
                $table->uuid('reviewer_id');
                $table->string('decision');              // approved|rejected|deferred|more_info_needed
                $table->text('comments')->nullable();
                $table->text('conditions')->nullable();
                $table->timestamp('reviewed_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('research_datasets')) {
            Schema::create('research_datasets', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('research_access_request_id')->constrained()->cascadeOnDelete();
                $table->string('dataset_name');
                $table->string('dataset_type');          // aggregate|de_identified|anonymised
                $table->json('included_fields')->nullable();
                $table->json('excluded_fields')->nullable();
                $table->string('time_range_from')->nullable();
                $table->string('time_range_to')->nullable();
                $table->integer('record_count_estimate')->nullable();
                $table->string('export_format')->nullable(); // csv|json|fhir_bundle
                $table->string('storage_path')->nullable();
                $table->string('status')->default('pending'); // pending|preparing|ready|delivered|expired
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('research_data_agreements')) {
            Schema::create('research_data_agreements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('research_access_request_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('researcher_profile_id')->constrained()->cascadeOnDelete();
                $table->text('agreement_text');
                $table->boolean('signed')->default(false);
                $table->timestamp('signed_at')->nullable();
                $table->string('signature_ip')->nullable();
                $table->date('effective_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('research_access_logs')) {
            Schema::create('research_access_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('research_access_request_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('researcher_profile_id')->constrained()->cascadeOnDelete();
                $table->string('action');                // dataset_viewed|dataset_downloaded|query_executed|export_requested
                $table->json('action_context')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();

                $table->index(['research_access_request_id', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('publication_reviews')) {
            Schema::create('publication_reviews', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('research_access_request_id')->constrained()->cascadeOnDelete();
                $table->string('publication_title');
                $table->string('target_journal')->nullable();
                $table->string('status')->default('submitted'); // submitted|under_review|approved|rejected|published
                $table->text('review_comments')->nullable();
                $table->uuid('reviewed_by')->nullable();
                $table->timestamp('submitted_at');
                $table->timestamp('reviewed_at')->nullable();
                $table->string('doi')->nullable();
                $table->timestamps();
            });
        }

        // ── 8. Country Expansion Framework ────────────────────────────────────
        if (! Schema::hasTable('country_legal_reviews')) {
            Schema::create('country_legal_reviews', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
                $table->string('review_type');           // data_protection|health_regulation|employment|tax|licensing
                $table->string('status')->default('pending'); // pending|in_progress|completed|requires_action
                $table->text('findings')->nullable();
                $table->text('required_actions')->nullable();
                $table->uuid('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->date('next_review_date')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('country_health_regulations')) {
            Schema::create('country_health_regulations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
                $table->string('regulation_name');
                $table->string('regulation_body');       // Ministry of Health / regulator name
                $table->string('regulation_type');       // emr|pharmacy|lab|telemedicine|data_privacy|insurance
                $table->text('description')->nullable();
                $table->boolean('compliance_required')->default(true);
                $table->string('compliance_status')->default('not_assessed'); // not_assessed|compliant|non_compliant|in_progress
                $table->date('assessment_date')->nullable();
                $table->text('compliance_notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('country_districts')) {
            Schema::create('country_districts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('region_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('government_district_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['region_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('country_payment_settings')) {
            Schema::create('country_payment_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
                $table->string('currency_code', 3);
                $table->string('currency_symbol', 10)->nullable();
                $table->json('supported_payment_methods')->nullable(); // mobile_money|card|bank|insurance|wallet
                $table->string('primary_payment_gateway')->nullable();
                $table->json('gateway_configs')->nullable();
                $table->boolean('tax_applicable')->default(false);
                $table->decimal('tax_rate_percent', 5, 2)->default(0);
                $table->string('tax_name')->nullable();
                $table->timestamps();

                $table->unique('country_id');
            });
        }

        if (! Schema::hasTable('country_public_health_rules')) {
            Schema::create('country_public_health_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
                $table->string('rule_name');
                $table->string('rule_type');             // mandatory_reporting|notifiable_disease|aggregate_submission|syndromic
                $table->text('description')->nullable();
                $table->string('reporting_frequency')->nullable(); // daily|weekly|monthly|real_time|event_driven
                $table->string('reporting_destination')->nullable(); // MOH|DHIS2|CDC|IDSR|custom
                $table->boolean('is_active')->default(true);
                $table->text('implementation_notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('country_data_residency_rules')) {
            Schema::create('country_data_residency_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
                $table->string('data_category');         // patient_records|financial|audit|aggregate|all
                $table->string('residency_requirement'); // must_remain_in_country|may_transfer_with_safeguards|unrestricted
                $table->json('permitted_countries')->nullable(); // ISO2 codes if cross-border transfer allowed
                $table->text('legal_basis')->nullable();
                $table->text('implementation_notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('country_launch_approvals')) {
            Schema::create('country_launch_approvals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('country_id')->constrained('countries')->cascadeOnDelete();
                $table->string('status')->default('pending'); // pending|in_progress|approved|rejected|withdrawn
                $table->text('checklist_summary')->nullable();
                $table->boolean('legal_review_complete')->default(false);
                $table->boolean('health_regulation_review_complete')->default(false);
                $table->boolean('language_pack_ready')->default(false);
                $table->boolean('payment_configured')->default(false);
                $table->boolean('pilot_facility_selected')->default(false);
                $table->boolean('data_residency_reviewed')->default(false);
                $table->uuid('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('approval_notes')->nullable();
                $table->timestamps();

                $table->unique('country_id');
            });
        }

        // ── 9. Subscription: PlanLimit ────────────────────────────────────────
        if (! Schema::hasTable('plan_limits')) {
            Schema::create('plan_limits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->string('limit_key');             // max_api_calls_per_month|max_storage_gb|max_users|max_reports_per_month
                $table->integer('limit_value');
                $table->string('limit_unit')->nullable(); // count|gb|requests|reports
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['plan_id', 'limit_key']);
            });
        }

        // ── 10. Import: ImportTemplate ────────────────────────────────────────
        if (! Schema::hasTable('import_templates')) {
            Schema::create('import_templates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('entity_type');           // patient|staff|inventory|appointments|clinical
                $table->string('version')->default('1.0');
                $table->json('required_columns');
                $table->json('optional_columns')->nullable();
                $table->json('column_validations')->nullable(); // per-column validation rules
                $table->string('example_file_path')->nullable();
                $table->boolean('is_active')->default(true);
                $table->uuid('created_by')->nullable();
                $table->timestamps();

                $table->unique(['entity_type', 'version']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_templates');
        Schema::dropIfExists('plan_limits');
        Schema::dropIfExists('country_launch_approvals');
        Schema::dropIfExists('country_data_residency_rules');
        Schema::dropIfExists('country_public_health_rules');
        Schema::dropIfExists('country_payment_settings');
        Schema::dropIfExists('country_districts');
        Schema::dropIfExists('country_health_regulations');
        Schema::dropIfExists('country_legal_reviews');
        Schema::dropIfExists('publication_reviews');
        Schema::dropIfExists('research_access_logs');
        Schema::dropIfExists('research_data_agreements');
        Schema::dropIfExists('research_datasets');
        Schema::dropIfExists('data_access_committee_reviews');
        Schema::dropIfExists('ethics_approvals');
        Schema::dropIfExists('researcher_profiles');
        Schema::dropIfExists('mapping_errors');
        Schema::dropIfExists('fhir_mapping_fields');
        Schema::dropIfExists('fhir_mapping_versions');
        Schema::dropIfExists('fhir_mappings');
        Schema::dropIfExists('compliance_cases');
        Schema::dropIfExists('access_reviews');
        Schema::dropIfExists('dose_warning_rules');
        Schema::dropIfExists('discharge_plans');
        Schema::dropIfExists('nursing_rounds');
        Schema::dropIfExists('inpatient_notes');
        Schema::dropIfExists('telemedicine_notes');
        Schema::dropIfExists('call_sessions');
        Schema::dropIfExists('virtual_waiting_rooms');
        Schema::dropIfExists('telemedicine_consents');
        Schema::dropIfExists('teleconsultations');
        Schema::dropIfExists('encounters');
    }
};
