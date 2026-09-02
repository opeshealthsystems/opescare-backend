<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 44a — Audit & Security Models
 *
 * Adds:
 *   appointment_audits        — immutable audit log per appointment action
 *   go_live_audits            — immutable audit log per go-live step
 *   attachment_audits         — immutable audit log per file attachment action
 *   telemedicine_audits       — immutable audit log per teleconsultation event
 *   triage_audits             — immutable audit log per triage action
 *   suspicious_access_flags   — flags raised by anomaly detection
 *   breach_reports            — data-breach incident records
 *   api_abuse_flags           — rate-limit/abuse flags per API consumer
 *   admin_action_reviews      — pending review of admin actions
 *   audit_exports             — record of audit-log export jobs
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointment_audits')) {
            Schema::create('appointment_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('appointment_id');
                $table->string('action');                  // created|rescheduled|cancelled|checked_in|no_show
                $table->uuid('performed_by')->nullable();
                $table->string('performed_by_role')->nullable();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();

                $table->index('appointment_id', 'appt_audit_appt_idx');
                $table->index('action', 'appt_audit_action_idx');
            });
        }

        if (! Schema::hasTable('go_live_audits')) {
            Schema::create('go_live_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->uuid('go_live_checklist_id')->nullable();
                $table->string('action');                  // checklist_item_completed|blocker_raised|blocker_resolved|approved|rejected
                $table->uuid('performed_by')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index('facility_id', 'gl_audit_facility_idx');
                $table->index('action', 'gl_audit_action_idx');
            });
        }

        if (! Schema::hasTable('attachment_audits')) {
            Schema::create('attachment_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_asset_id');
                $table->string('action');                  // uploaded|classified|attached|downloaded|archived|deleted
                $table->uuid('performed_by')->nullable();
                $table->string('ip_address')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index('file_asset_id', 'att_audit_asset_idx');
                $table->index('action', 'att_audit_action_idx');
            });
        }

        if (! Schema::hasTable('telemedicine_audits')) {
            Schema::create('telemedicine_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('teleconsultation_id');
                $table->string('action');                  // scheduled|consent_granted|consent_revoked|call_started|call_ended|cancelled
                $table->uuid('performed_by')->nullable();
                $table->string('ip_address')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index('teleconsultation_id', 'tele_audit_consult_idx');
                $table->index('action', 'tele_audit_action_idx');
            });
        }

        if (! Schema::hasTable('triage_audits')) {
            Schema::create('triage_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('visit_id')->nullable();
                $table->uuid('triage_record_id')->nullable();
                $table->string('action');                  // assessed|score_assigned|escalated|reassessed|overridden
                $table->uuid('performed_by')->nullable();
                $table->string('performed_by_role')->nullable();
                $table->json('payload')->nullable();
                $table->text('clinical_note')->nullable();
                $table->timestamps();

                $table->index('visit_id', 'triage_audit_visit_idx');
                $table->index('action', 'triage_audit_action_idx');
            });
        }

        if (! Schema::hasTable('suspicious_access_flags')) {
            Schema::create('suspicious_access_flags', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->string('flag_type');               // bulk_download|off_hours_access|unusual_patient_count|rapid_succession
                $table->string('severity');                // low|medium|high|critical
                $table->json('evidence')->nullable();      // access events that triggered the flag
                $table->string('status')->default('open'); // open|reviewed|dismissed|escalated
                $table->uuid('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();

                $table->index('user_id', 'saf_user_idx');
                $table->index('status', 'saf_status_idx');
                $table->index('severity', 'saf_severity_idx');
            });
        }

        if (! Schema::hasTable('breach_reports')) {
            Schema::create('breach_reports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->text('description');
                $table->string('breach_type');             // unauthorized_access|data_leak|ransomware|lost_device|insider_threat
                $table->string('severity');                // low|medium|high|critical
                $table->string('status')->default('open'); // open|investigating|contained|notified|closed
                $table->timestamp('discovered_at');
                $table->timestamp('contained_at')->nullable();
                $table->timestamp('reported_to_authority_at')->nullable();
                $table->integer('estimated_affected_records')->nullable();
                $table->json('affected_data_types')->nullable();
                $table->uuid('reported_by');
                $table->uuid('assigned_to')->nullable();
                $table->timestamps();

                $table->index('status', 'breach_status_idx');
                $table->index('severity', 'breach_severity_idx');
            });
        }

        if (! Schema::hasTable('api_abuse_flags')) {
            Schema::create('api_abuse_flags', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('api_consumer_type');       // key|sdk|partner|user
                $table->string('api_consumer_id');
                $table->string('flag_type');               // rate_limit_breach|scraping|unusual_volume|banned_endpoint
                $table->integer('request_count')->default(0);
                $table->string('time_window');             // e.g. "1m" | "1h" | "1d"
                $table->string('status')->default('open'); // open|reviewed|blocked|dismissed
                $table->json('evidence')->nullable();
                $table->uuid('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['api_consumer_type', 'api_consumer_id'], 'aaf_consumer_idx');
                $table->index('status', 'aaf_status_idx');
            });
        }

        if (! Schema::hasTable('admin_action_reviews')) {
            Schema::create('admin_action_reviews', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('admin_action_log_id')->nullable();
                $table->string('action_type');             // setting_change|feature_flag|module_toggle|user_impersonation
                $table->string('status')->default('pending'); // pending|approved|rejected
                $table->uuid('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();

                $table->index('status', 'aar_status_idx');
                $table->index('action_type', 'aar_action_type_idx');
            });
        }

        if (! Schema::hasTable('audit_exports')) {
            Schema::create('audit_exports', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('requested_by');
                $table->string('export_type');             // full|date_range|user|facility|module
                $table->json('filters')->nullable();
                $table->string('format');                  // csv|json|pdf
                $table->string('status')->default('pending'); // pending|processing|ready|expired|failed
                $table->string('file_path')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index('requested_by', 'ae_requester_idx');
                $table->index('status', 'ae_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_exports');
        Schema::dropIfExists('admin_action_reviews');
        Schema::dropIfExists('api_abuse_flags');
        Schema::dropIfExists('breach_reports');
        Schema::dropIfExists('suspicious_access_flags');
        Schema::dropIfExists('triage_audits');
        Schema::dropIfExists('telemedicine_audits');
        Schema::dropIfExists('attachment_audits');
        Schema::dropIfExists('go_live_audits');
        Schema::dropIfExists('appointment_audits');
    }
};
