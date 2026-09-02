<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 44b — Domain Gap Models
 *
 * Adds:
 *   queues                             — dept/service queue entity (wraps queue tickets)
 *   search_permission_filters          — per-user/role search access rules
 *   analytics_snapshots               — pre-computed analytics snapshots
 *   report_exports                    — export job records for report definitions
 *   telemedicine_payment_links        — payment link for teleconsultation fee
 *   bed_assignments                   — current and historical bed assignment records
 *   inpatient_medication_administrations — MAR records for inpatient medication
 *   bed_occupancy_snapshots           — periodic bed occupancy state captures
 *   clinical_rule_sources             — traceable clinical evidence sources
 *   offline_cache_policies            — per-facility/role offline data policies
 *   sync_attempts                     — per-device sync attempt log
 *   device_sync_states                — device-level sync progress
 *   mobile_devices                    — registered mobile devices
 *   subscription_payments             — SaaS subscription payment records
 *   usage_limits                      — plan-level feature usage limits
 *   trial_periods                     — subscription trial configuration
 *   triage_vital_signs                — vital-sign readings captured at triage
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Queue Management ──────────────────────────────────────────────────

        if (! Schema::hasTable('queues')) {
            Schema::create('queues', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->uuid('department_id')->nullable();
                $table->string('name');
                $table->string('queue_type')->default('general'); // general|emergency|vip|specialist
                $table->boolean('is_active')->default(true);
                $table->integer('current_serving')->default(0);
                $table->integer('waiting_count')->default(0);
                $table->timestamps();

                $table->index(['facility_id', 'is_active'], 'queues_facility_active_idx');
            });
        }

        // ── Search ────────────────────────────────────────────────────────────

        if (! Schema::hasTable('search_permission_filters')) {
            Schema::create('search_permission_filters', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('role');                    // staff|doctor|nurse|insurance|support|etc
                $table->string('resource_type');           // Patient|Facility|Medicine|Lab|etc
                $table->boolean('can_search')->default(false);
                $table->boolean('requires_audit_log')->default(true);
                $table->json('allowed_fields')->nullable(); // which fields are returned
                $table->json('restrictions')->nullable();  // additional scope restrictions
                $table->timestamps();

                $table->unique(['role', 'resource_type'], 'spf_role_resource_unique');
            });
        }

        // ── Analytics ─────────────────────────────────────────────────────────

        if (! Schema::hasTable('analytics_snapshots')) {
            Schema::create('analytics_snapshots', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('snapshot_type');           // daily|weekly|monthly|quarterly
                $table->string('scope_type');              // facility|organization|platform
                $table->uuid('scope_id')->nullable();
                $table->date('period_start');
                $table->date('period_end');
                $table->json('metrics');                   // key => value map
                $table->boolean('is_published')->default(false);
                $table->timestamps();

                $table->index(['snapshot_type', 'scope_type', 'scope_id'], 'as_scope_idx');
                $table->index('period_start', 'as_period_idx');
            });
        }

        if (! Schema::hasTable('report_exports')) {
            Schema::create('report_exports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('report_definition_id')->nullable();
                $table->uuid('requested_by');
                $table->json('parameters')->nullable();
                $table->string('format');                  // csv|pdf|xlsx|json
                $table->string('status')->default('pending'); // pending|processing|ready|failed|expired
                $table->string('file_path')->nullable();
                $table->integer('row_count')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index('requested_by', 're_requester_idx');
                $table->index('status', 're_status_idx');
            });
        }

        // ── Telemedicine ──────────────────────────────────────────────────────

        if (! Schema::hasTable('telemedicine_payment_links')) {
            Schema::create('telemedicine_payment_links', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('teleconsultation_id');
                $table->uuid('patient_id');
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('USD');
                $table->string('payment_url')->nullable();
                $table->string('reference')->nullable();
                $table->string('status')->default('pending'); // pending|paid|expired|cancelled
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index('teleconsultation_id', 'tpl_consult_idx');
                $table->index('status', 'tpl_status_idx');
            });
        }

        // ── Ward Management ───────────────────────────────────────────────────

        if (! Schema::hasTable('bed_assignments')) {
            Schema::create('bed_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('admission_id');
                $table->uuid('bed_id');
                $table->uuid('patient_id');
                $table->uuid('assigned_by')->nullable();
                $table->timestamp('assigned_at');
                $table->timestamp('released_at')->nullable();
                $table->string('status')->default('active'); // active|released|transferred
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('admission_id', 'ba_admission_idx');
                $table->index('bed_id', 'ba_bed_idx');
                $table->index('status', 'ba_status_idx');
            });
        }

        if (! Schema::hasTable('inpatient_medication_administrations')) {
            Schema::create('inpatient_medication_administrations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('admission_id');
                $table->uuid('patient_id');
                $table->uuid('medicine_id')->nullable();
                $table->string('medicine_name');
                $table->string('dose');
                $table->string('route');                   // oral|IV|IM|SC|topical|etc
                $table->timestamp('scheduled_at');
                $table->timestamp('administered_at')->nullable();
                $table->string('status')->default('scheduled'); // scheduled|administered|missed|held|refused
                $table->uuid('administered_by')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('cdss_checked')->default(false);
                $table->timestamps();

                // CDSS Safety: medication administration records must never be silently altered.
                $table->index('admission_id', 'ima_admission_idx');
                $table->index('status', 'ima_status_idx');
                $table->index('scheduled_at', 'ima_scheduled_idx');
            });
        }

        if (! Schema::hasTable('bed_occupancy_snapshots')) {
            Schema::create('bed_occupancy_snapshots', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->uuid('ward_id')->nullable();
                $table->integer('total_beds');
                $table->integer('occupied_beds');
                $table->integer('available_beds');
                $table->integer('reserved_beds')->default(0);
                $table->float('occupancy_rate');           // 0.0 – 100.0
                $table->timestamp('captured_at');
                $table->timestamps();

                $table->index('facility_id', 'bos_facility_idx');
                $table->index('captured_at', 'bos_captured_idx');
            });
        }

        // ── Clinical Decision Support ─────────────────────────────────────────

        if (! Schema::hasTable('clinical_rule_sources')) {
            Schema::create('clinical_rule_sources', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('clinical_rule_id');
                $table->string('source_type');             // guideline|study|database|expert_consensus
                $table->string('title');
                $table->string('publication')->nullable();
                $table->string('url')->nullable();
                $table->string('doi')->nullable();
                $table->year('year')->nullable();
                $table->string('evidence_level')->nullable(); // I|II|III|IV|V (Oxford)
                $table->timestamps();

                $table->index('clinical_rule_id', 'crs_rule_idx');
            });
        }

        // ── Offline Sync ──────────────────────────────────────────────────────

        if (! Schema::hasTable('offline_cache_policies')) {
            Schema::create('offline_cache_policies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('scope_type');              // facility|role|organization
                $table->string('scope_id');
                $table->string('resource_type');           // Patient|Appointment|etc
                $table->boolean('is_cacheable')->default(false);
                $table->integer('max_records')->default(0); // 0 = no caching
                $table->integer('ttl_minutes')->default(60);
                $table->json('excluded_fields')->nullable(); // fields never cached
                $table->text('policy_note')->nullable();
                $table->timestamps();

                // Security: full EMR must NEVER be cached by default.
                $table->unique(['scope_type', 'scope_id', 'resource_type'], 'ocp_scope_unique');
            });
        }

        if (! Schema::hasTable('sync_attempts')) {
            Schema::create('sync_attempts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('sync_job_id')->nullable();
                $table->uuid('device_id')->nullable();
                $table->uuid('user_id');
                $table->string('direction');               // push|pull
                $table->string('status');                  // pending|running|success|failed|conflict
                $table->integer('records_synced')->default(0);
                $table->integer('conflicts_found')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index('sync_job_id', 'sa_job_idx');
                $table->index('user_id', 'sa_user_idx');
                $table->index('status', 'sa_status_idx');
            });
        }

        if (! Schema::hasTable('device_sync_states')) {
            Schema::create('device_sync_states', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('device_id');
                $table->uuid('user_id');
                $table->string('resource_type');
                $table->timestamp('last_synced_at')->nullable();
                $table->string('last_sync_status')->nullable(); // success|failed|conflict
                $table->integer('pending_push_count')->default(0);
                $table->integer('pending_pull_count')->default(0);
                $table->timestamps();

                $table->unique(['device_id', 'user_id', 'resource_type'], 'dss_device_user_type_unique');
            });
        }

        // ── Mobile ────────────────────────────────────────────────────────────

        if (! Schema::hasTable('mobile_devices')) {
            Schema::create('mobile_devices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('device_identifier');       // unique per hardware
                $table->string('platform');                // ios|android
                $table->string('app_version')->nullable();
                $table->string('os_version')->nullable();
                $table->string('push_token')->nullable();
                $table->boolean('is_trusted')->default(false);
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->index('user_id', 'md_user_idx');
                $table->unique(['user_id', 'device_identifier'], 'md_user_device_unique');
            });
        }

        // ── Subscription / SaaS ───────────────────────────────────────────────

        if (! Schema::hasTable('subscription_payments')) {
            Schema::create('subscription_payments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('organization_subscription_id');
                $table->uuid('subscription_invoice_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('USD');
                $table->string('payment_method')->nullable(); // card|bank|mobile_money
                $table->string('transaction_reference')->nullable();
                $table->string('status');                  // pending|confirmed|failed|refunded
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index('organization_subscription_id', 'sp_sub_idx');
                $table->index('status', 'sp_status_idx');
            });
        }

        if (! Schema::hasTable('usage_limits')) {
            Schema::create('usage_limits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('subscription_plan_id');
                $table->string('feature_key');             // patients|staff|facilities|api_calls|storage_mb
                $table->integer('limit_value');            // -1 = unlimited
                $table->string('reset_period')->nullable(); // daily|monthly|never
                $table->timestamps();

                $table->unique(['subscription_plan_id', 'feature_key'], 'ul_plan_feature_unique');
            });
        }

        if (! Schema::hasTable('trial_periods')) {
            Schema::create('trial_periods', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('subscription_plan_id');
                $table->uuid('organization_id');
                $table->integer('duration_days');
                $table->timestamp('started_at');
                $table->timestamp('ends_at');
                $table->boolean('converted')->default(false);
                $table->timestamp('converted_at')->nullable();
                $table->timestamps();

                $table->index('organization_id', 'tp_org_idx');
                $table->index('ends_at', 'tp_ends_idx');
            });
        }

        // ── Triage Vitals ─────────────────────────────────────────────────────

        if (! Schema::hasTable('triage_vital_signs')) {
            Schema::create('triage_vital_signs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('triage_record_id');
                $table->uuid('visit_id')->nullable();
                $table->uuid('patient_id');
                $table->decimal('temperature', 5, 2)->nullable();     // °C
                $table->integer('pulse_rate')->nullable();             // bpm
                $table->integer('respiratory_rate')->nullable();       // breaths/min
                $table->integer('systolic_bp')->nullable();            // mmHg
                $table->integer('diastolic_bp')->nullable();           // mmHg
                $table->integer('oxygen_saturation')->nullable();      // %
                $table->decimal('weight_kg', 6, 2)->nullable();
                $table->decimal('height_cm', 6, 2)->nullable();
                $table->integer('gcs_score')->nullable();              // 3-15
                $table->integer('pain_score')->nullable();             // 0-10
                $table->string('consciousness_level')->nullable();     // alert|voice|pain|unresponsive
                $table->uuid('recorded_by');
                $table->timestamp('recorded_at');
                $table->timestamps();

                // CDSS Safety: vital signs are clinical data — must be recorded accurately.
                // Never auto-correct or silently overwrite vital sign readings.
                $table->index('triage_record_id', 'tvs_triage_idx');
                $table->index('patient_id', 'tvs_patient_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('triage_vital_signs');
        Schema::dropIfExists('trial_periods');
        Schema::dropIfExists('usage_limits');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('mobile_devices');
        Schema::dropIfExists('device_sync_states');
        Schema::dropIfExists('sync_attempts');
        Schema::dropIfExists('offline_cache_policies');
        Schema::dropIfExists('clinical_rule_sources');
        Schema::dropIfExists('bed_occupancy_snapshots');
        Schema::dropIfExists('inpatient_medication_administrations');
        Schema::dropIfExists('bed_assignments');
        Schema::dropIfExists('telemedicine_payment_links');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('analytics_snapshots');
        Schema::dropIfExists('search_permission_filters');
        Schema::dropIfExists('queues');
    }
};
