<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 47 — Governance & Maturity Models
 *
 * Adds:
 *   record_export_requests        — GDPR data portability requests
 *   research_requests             — initial research access submission
 *   country_profiles              — comprehensive country onboarding profile
 *   country_language_packs        — translated language pack per country
 *   legal_document_change_logs    — audit log for legal document changes
 *   certification_expiries        — certification expiry tracking
 *   kpi_dashboards                — KPI dashboard definitions
 *   trust_badges                  — trust/compliance badge definitions
 *   trust_badge_assignments       — badges granted to facilities/orgs
 *   trust_badge_criteria          — criteria for earning a badge
 *   trust_badge_verifications     — verification run for a badge
 *   trust_badge_audits            — audit log for badge actions
 *   integration_listings          — marketplace integration listings
 *   integration_categories        — marketplace categories
 *   integration_badges            — badges on marketplace listings
 *   integration_reviews           — user reviews of marketplace listings
 *   integration_listing_audits    — audit log for listing changes
 *   facility_devices              — physical devices registered to a facility
 *   device_assignments            — device-to-staff/role assignments
 *   device_status_logs            — device status/health log
 *   device_revocations            — device revocation records
 *   ai_model_registries           — AI/ML model governance registry
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── GDPR / Patient Rights ─────────────────────────────────────────────

        if (! Schema::hasTable('record_export_requests')) {
            Schema::create('record_export_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('patient_id');
                $table->string('request_type')->default('full'); // full|partial|specific_date_range
                $table->json('requested_sections')->nullable();  // EMR|billing|documents|all
                $table->string('format')->default('pdf');        // pdf|json|csv
                $table->string('status')->default('pending');    // pending|processing|ready|expired|failed
                $table->string('file_path')->nullable();
                $table->timestamp('response_due_date')->nullable();
                $table->timestamp('fulfilled_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->uuid('handled_by')->nullable();
                $table->timestamps();

                $table->index('patient_id', 'rer_patient_idx');
                $table->index('status', 'rer_status_idx');
                $table->index('response_due_date', 'rer_due_idx');
            });
        }

        // ── Research Access ────────────────────────────────────────────────────

        if (! Schema::hasTable('research_requests')) {
            Schema::create('research_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('researcher_profile_id');
                $table->uuid('ethics_approval_id')->nullable();
                $table->string('title');
                $table->text('purpose');
                $table->string('dataset_type');               // aggregate|de_identified|anonymised
                $table->json('requested_fields')->nullable();
                $table->string('status')->default('submitted'); // submitted|dac_review|approved|rejected|withdrawn
                $table->date('data_access_start')->nullable();
                $table->date('data_access_end')->nullable();
                $table->uuid('submitted_by');
                $table->timestamps();

                // Security: only de-identified data may be granted; raw patient data is blocked.
                $table->index('researcher_profile_id', 'rr_researcher_idx');
                $table->index('status', 'rr_status_idx');
            });
        }

        // ── Country Expansion ─────────────────────────────────────────────────

        if (! Schema::hasTable('country_profiles')) {
            Schema::create('country_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('country_id');
                $table->string('official_name');
                $table->string('local_name')->nullable();
                $table->string('hie_status')->nullable();        // none|planned|partial|operational
                $table->string('health_system_type')->nullable(); // public|private|mixed
                $table->string('primary_language');
                $table->json('official_languages')->nullable();
                $table->string('currency_code', 3)->nullable();
                $table->string('timezone')->nullable();
                $table->string('data_residency_requirement')->nullable(); // local|regional|none
                $table->string('gdpr_equivalent')->nullable();
                $table->boolean('launch_approved')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique('country_id', 'cp_country_unique');
            });
        }

        if (! Schema::hasTable('country_language_packs')) {
            Schema::create('country_language_packs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('country_id');
                $table->string('language_code', 10);          // en|fr|sw|ar|pt etc
                $table->string('language_name');
                $table->boolean('is_primary')->default(false);
                $table->string('translation_status')->default('pending'); // pending|partial|complete|reviewed
                $table->json('missing_keys')->nullable();
                $table->timestamp('last_reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['country_id', 'language_code'], 'clp_country_lang_unique');
            });
        }

        // ── Legal Documents ────────────────────────────────────────────────────

        if (! Schema::hasTable('legal_document_change_logs')) {
            Schema::create('legal_document_change_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('legal_document_id');
                $table->uuid('legal_document_version_id')->nullable();
                $table->string('change_type');           // created|updated|published|archived|translated
                $table->string('changed_by');
                $table->text('change_summary')->nullable();
                $table->json('diff')->nullable();
                $table->timestamps();

                $table->index('legal_document_id', 'ldcl_doc_idx');
            });
        }

        // ── Certification ─────────────────────────────────────────────────────

        if (! Schema::hasTable('certification_expiries')) {
            Schema::create('certification_expiries', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('certification_id');
                $table->uuid('holder_id');
                $table->string('holder_type');             // Staff|Facility|DeveloperApp
                $table->timestamp('issued_at');
                $table->timestamp('expires_at');
                $table->boolean('renewal_notified')->default(false);
                $table->boolean('expired')->default(false);
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->index('holder_id', 'ce_holder_idx');
                $table->index('expires_at', 'ce_expires_idx');
                $table->index('expired', 'ce_expired_idx');
            });
        }

        // ── KPI / Analytics ───────────────────────────────────────────────────

        if (! Schema::hasTable('kpi_dashboards')) {
            Schema::create('kpi_dashboards', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('target_role');             // facility_admin|hospital_director|super_admin|public_health
                $table->json('metric_keys')->nullable();   // ordered list of MetricDefinition keys
                $table->boolean('is_published')->default(false);
                $table->json('layout')->nullable();        // grid layout config
                $table->timestamps();

                $table->index('target_role', 'kd_role_idx');
            });
        }

        // ── Trust Badges ──────────────────────────────────────────────────────

        if (! Schema::hasTable('trust_badges')) {
            Schema::create('trust_badges', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('badge_type');              // compliance|security|integration|quality
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('trust_badge_assignments')) {
            Schema::create('trust_badge_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('trust_badge_id');
                $table->string('holder_type');             // Facility|Organization|DeveloperApp
                $table->uuid('holder_id');
                $table->string('status')->default('active'); // active|revoked|expired
                $table->timestamp('granted_at');
                $table->timestamp('expires_at')->nullable();
                $table->uuid('granted_by');
                $table->timestamps();

                $table->index(['holder_type', 'holder_id'], 'tba_holder_idx');
                $table->index('trust_badge_id', 'tba_badge_idx');
            });
        }

        if (! Schema::hasTable('trust_badge_criteria')) {
            Schema::create('trust_badge_criteria', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('trust_badge_id');
                $table->string('criterion_key');
                $table->text('description');
                $table->boolean('is_mandatory')->default(true);
                $table->timestamps();

                $table->index('trust_badge_id', 'tbc_badge_idx');
            });
        }

        if (! Schema::hasTable('trust_badge_verifications')) {
            Schema::create('trust_badge_verifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('trust_badge_id');
                $table->uuid('trust_badge_assignment_id')->nullable();
                $table->string('holder_type');
                $table->uuid('holder_id');
                $table->string('status');                  // pending|passed|failed
                $table->json('criteria_results')->nullable();
                $table->uuid('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['holder_type', 'holder_id'], 'tbv_holder_idx');
            });
        }

        if (! Schema::hasTable('trust_badge_audits')) {
            Schema::create('trust_badge_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('trust_badge_assignment_id');
                $table->string('action');                  // granted|revoked|renewed|expired
                $table->uuid('performed_by')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index('trust_badge_assignment_id', 'tbaud_assignment_idx');
            });
        }

        // ── Marketplace ───────────────────────────────────────────────────────

        if (! Schema::hasTable('integration_categories')) {
            Schema::create('integration_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('integration_listings')) {
            Schema::create('integration_listings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('developer_app_id')->nullable();
                $table->uuid('integration_category_id')->nullable();
                $table->string('name');
                $table->text('short_description')->nullable();
                $table->text('description')->nullable();
                $table->string('website')->nullable();
                $table->string('status')->default('pending'); // pending|approved|rejected|suspended
                $table->boolean('is_featured')->default(false);
                $table->timestamps();

                $table->index('status', 'il_status_idx');
                $table->index('integration_category_id', 'il_category_idx');
            });
        }

        if (! Schema::hasTable('integration_badges')) {
            Schema::create('integration_badges', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('integration_listing_id');
                $table->uuid('trust_badge_id')->nullable();
                $table->string('label');
                $table->timestamps();

                $table->index('integration_listing_id', 'ib_listing_idx');
            });
        }

        if (! Schema::hasTable('integration_reviews')) {
            Schema::create('integration_reviews', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('integration_listing_id');
                $table->uuid('reviewer_id');
                $table->string('reviewer_type');           // Facility|Organization
                $table->integer('rating');                 // 1-5
                $table->text('review_text')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamps();

                $table->index('integration_listing_id', 'ir_listing_idx');
            });
        }

        if (! Schema::hasTable('integration_listing_audits')) {
            Schema::create('integration_listing_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('integration_listing_id');
                $table->string('action');                  // submitted|approved|rejected|suspended|updated
                $table->uuid('performed_by')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index('integration_listing_id', 'ila_listing_idx');
            });
        }

        // ── Facility Devices ──────────────────────────────────────────────────

        if (! Schema::hasTable('facility_devices')) {
            Schema::create('facility_devices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->string('device_type');             // tablet|desktop|kiosk|scanner|printer|qr_reader
                $table->string('name');
                $table->string('serial_number')->nullable();
                $table->string('model')->nullable();
                $table->string('status')->default('active'); // active|inactive|maintenance|retired
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->index('facility_id', 'fd_facility_idx');
                $table->index('status', 'fd_status_idx');
            });
        }

        if (! Schema::hasTable('device_assignments')) {
            Schema::create('device_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_device_id');
                $table->string('assigned_to_type')->nullable(); // Staff|Station|Department
                $table->uuid('assigned_to_id')->nullable();
                $table->timestamp('assigned_at');
                $table->timestamp('returned_at')->nullable();
                $table->uuid('assigned_by')->nullable();
                $table->timestamps();

                $table->index('facility_device_id', 'da_device_idx');
            });
        }

        if (! Schema::hasTable('device_status_logs')) {
            Schema::create('device_status_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_device_id');
                $table->string('status');                  // active|inactive|maintenance|retired|error
                $table->text('note')->nullable();
                $table->uuid('logged_by')->nullable();
                $table->timestamps();

                $table->index('facility_device_id', 'dsl_device_idx');
            });
        }

        if (! Schema::hasTable('device_revocations')) {
            Schema::create('device_revocations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_device_id');
                $table->string('reason');                  // lost|stolen|compromised|decommissioned
                $table->uuid('revoked_by');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('facility_device_id', 'dr_device_idx');
            });
        }

        // ── AI / ML Governance ────────────────────────────────────────────────

        if (! Schema::hasTable('ai_model_registries')) {
            Schema::create('ai_model_registries', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('model_name');
                $table->string('model_version');
                $table->text('purpose');
                $table->text('training_data_summary')->nullable();
                $table->string('risk_level');              // low|medium|high|critical
                $table->json('approved_uses')->nullable();
                $table->json('blocked_uses')->nullable();
                $table->string('approval_status')->default('pending'); // pending|approved|rejected|deprecated
                $table->uuid('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->json('monitoring_metrics')->nullable();
                $table->string('rollback_version')->nullable();
                $table->timestamps();

                // CDSS Safety: AI models must NEVER replace clinical judgment.
                // Every approved use must specify "advisory only" constraints.
                $table->index('approval_status', 'amr_status_idx');
                $table->index('risk_level', 'amr_risk_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_model_registries');
        Schema::dropIfExists('device_revocations');
        Schema::dropIfExists('device_status_logs');
        Schema::dropIfExists('device_assignments');
        Schema::dropIfExists('facility_devices');
        Schema::dropIfExists('integration_listing_audits');
        Schema::dropIfExists('integration_reviews');
        Schema::dropIfExists('integration_badges');
        Schema::dropIfExists('integration_listings');
        Schema::dropIfExists('integration_categories');
        Schema::dropIfExists('trust_badge_audits');
        Schema::dropIfExists('trust_badge_verifications');
        Schema::dropIfExists('trust_badge_criteria');
        Schema::dropIfExists('trust_badge_assignments');
        Schema::dropIfExists('trust_badges');
        Schema::dropIfExists('kpi_dashboards');
        Schema::dropIfExists('certification_expiries');
        Schema::dropIfExists('legal_document_change_logs');
        Schema::dropIfExists('country_language_packs');
        Schema::dropIfExists('country_profiles');
        Schema::dropIfExists('research_requests');
        Schema::dropIfExists('record_export_requests');
    }
};
