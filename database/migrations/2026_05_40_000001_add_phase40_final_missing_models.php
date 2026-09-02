<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 40 — Final Missing Models Migration
 *
 * Adds tables for:
 *   - Patient rights: patient_rights_requests, record_correction_decisions,
 *     guardian_relationships
 *   - Go-live granular: go_live_checklists, go_live_checklist_items,
 *     go_live_blockers, go_live_approvals
 *   - File storage: virus_scan_results, file_classifications,
 *     signed_download_tokens
 *   - Analytics: dashboard_metrics, report_definitions, analytics_access_logs
 *   - Geography: cities
 *
 * All tables are idempotent (Schema::hasTable guards).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Patient Rights ─────────────────────────────────────────────────
        if (! Schema::hasTable('patient_rights_requests')) {
            Schema::create('patient_rights_requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete();
                $table->string('request_type');        // data_export|deletion|correction|objection|restrict_processing|portability
                $table->text('reason')->nullable();
                $table->string('status')->default('pending'); // pending|under_review|completed|rejected|partially_fulfilled
                $table->uuid('reviewed_by')->nullable();
                $table->text('response_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('fulfilled_at')->nullable();
                $table->date('response_due_date')->nullable();  // GDPR 30-day window
                $table->timestamps();

                $table->index(['patient_id', 'status']);
            });
        }

        if (! Schema::hasTable('record_correction_decisions')) {
            Schema::create('record_correction_decisions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('correction_request_id')->constrained('correction_requests')->cascadeOnDelete();
                $table->uuid('decided_by');
                $table->string('decision');            // approved|rejected|partial
                $table->text('decision_reason');
                $table->json('approved_changes')->nullable();  // Exact field changes approved
                $table->json('rejected_changes')->nullable();  // Fields rejected and why
                $table->timestamp('decided_at');
                $table->boolean('patient_notified')->default(false);
                $table->timestamp('patient_notified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('guardian_relationships')) {
            Schema::create('guardian_relationships', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('patient_id')->constrained()->cascadeOnDelete(); // the minor
                $table->uuid('guardian_user_id')->nullable();
                $table->string('guardian_name');
                $table->string('guardian_phone')->nullable();
                $table->string('guardian_email')->nullable();
                $table->string('relationship_type');   // parent|legal_guardian|court_appointed|emergency_contact
                $table->boolean('has_medical_consent')->default(true);
                $table->boolean('has_data_access')->default(false);
                $table->string('legal_document_reference')->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable(); // null = until patient turns 18
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->uuid('recorded_by')->nullable();
                $table->timestamps();

                $table->index(['patient_id', 'is_active']);
            });
        }

        // ── 2. Go-Live Granular ───────────────────────────────────────────────
        if (! Schema::hasTable('go_live_checklists')) {
            Schema::create('go_live_checklists', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('facility_id')->constrained('care_facilities')->cascadeOnDelete();
                $table->string('checklist_version')->default('1.0');
                $table->string('status')->default('draft'); // draft|in_progress|submitted|approved|rejected
                $table->integer('total_items')->default(0);
                $table->integer('completed_items')->default(0);
                $table->integer('blocker_count')->default(0);
                $table->uuid('submitted_by')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->unique('facility_id'); // One checklist per facility
            });
        }

        if (! Schema::hasTable('go_live_checklist_items')) {
            Schema::create('go_live_checklist_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('go_live_checklist_id')->constrained()->cascadeOnDelete();
                $table->string('category');            // clinical|billing|admin|compliance|technical|staffing
                $table->string('item_key');
                $table->text('item_label');
                $table->boolean('is_required')->default(true);
                $table->boolean('is_completed')->default(false);
                $table->string('completion_evidence')->nullable();
                $table->uuid('completed_by')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['go_live_checklist_id', 'is_completed']);
            });
        }

        if (! Schema::hasTable('go_live_blockers')) {
            Schema::create('go_live_blockers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('go_live_checklist_id')->constrained()->cascadeOnDelete();
                $table->string('blocker_type');        // critical|major|minor
                $table->text('description');
                $table->text('resolution_required');
                $table->string('status')->default('open'); // open|in_progress|resolved|waived
                $table->uuid('assigned_to')->nullable();
                $table->uuid('resolved_by')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('go_live_approvals')) {
            Schema::create('go_live_approvals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('go_live_checklist_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('facility_id')->constrained('care_facilities')->cascadeOnDelete();
                $table->string('status')->default('pending'); // pending|approved|rejected|conditional
                $table->uuid('approved_by')->nullable();
                $table->text('approval_notes')->nullable();
                $table->json('conditions')->nullable();       // Conditional approval conditions
                $table->timestamp('approved_at')->nullable();
                $table->date('go_live_date')->nullable();
                $table->timestamps();

                $table->unique('facility_id');
            });
        }

        // ── 3. File Storage Extras ────────────────────────────────────────────
        if (! Schema::hasTable('virus_scan_results')) {
            Schema::create('virus_scan_results', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_asset_id')->nullable();
                $table->string('file_path');
                $table->string('scanner');             // clamav|sentinel|placeholder
                $table->string('status');              // pending|clean|infected|error
                $table->string('threat_name')->nullable();
                $table->text('scan_output')->nullable();
                $table->timestamp('scanned_at');
                $table->timestamps();

                $table->index(['file_asset_id', 'status']);
            });
        }

        if (! Schema::hasTable('file_classifications')) {
            Schema::create('file_classifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_asset_id');
                $table->string('classification_type'); // medical_record|billing|lab|imaging|legal|consent|other
                $table->string('sensitivity_level');   // public|internal|confidential|restricted
                $table->json('tags')->nullable();
                $table->uuid('classified_by')->nullable();
                $table->timestamp('classified_at');
                $table->timestamps();

                $table->unique('file_asset_id');
                $table->index(['classification_type', 'sensitivity_level']);
            });
        }

        if (! Schema::hasTable('signed_download_tokens')) {
            Schema::create('signed_download_tokens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('file_asset_id');
                $table->string('token', 128)->unique();
                $table->uuid('requested_by');
                $table->string('purpose')->nullable();  // download|preview|share
                $table->integer('max_uses')->default(1);
                $table->integer('use_count')->default(0);
                $table->timestamp('expires_at');
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['token', 'expires_at']);
            });
        }

        // ── 4. Analytics Extras ───────────────────────────────────────────────
        if (! Schema::hasTable('dashboard_metrics')) {
            Schema::create('dashboard_metrics', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('metric_key')->unique();
                $table->string('metric_name');
                $table->string('metric_category');     // operational|clinical|financial|quality
                $table->string('aggregation_method');  // count|sum|average|rate|ratio|custom
                $table->string('data_source');         // table and optional column
                $table->json('filters')->nullable();
                $table->string('display_format')->nullable(); // number|percentage|currency|duration
                $table->string('unit')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('report_definitions')) {
            Schema::create('report_definitions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('report_type');         // operational|clinical|financial|audit|public_health
                $table->text('description')->nullable();
                $table->json('parameters_schema')->nullable(); // JSON Schema for report params
                $table->json('metric_keys')->nullable();       // Which dashboard_metrics to include
                $table->json('export_formats')->nullable();    // csv|pdf|json|excel
                $table->string('schedule')->nullable();        // daily|weekly|monthly|none
                $table->boolean('requires_facility_scope')->default(true);
                $table->boolean('is_active')->default(true);
                $table->uuid('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('analytics_access_logs')) {
            Schema::create('analytics_access_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('actor_id');
                $table->string('actor_role')->nullable();
                $table->foreignUuid('facility_id')->nullable()->constrained('care_facilities')->nullOnDelete();
                $table->string('resource_type');       // dashboard|report|metric|export
                $table->string('resource_id')->nullable();
                $table->string('action');              // viewed|exported|downloaded|filtered
                $table->json('parameters')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();

                $table->index(['actor_id', 'occurred_at']);
                $table->index(['facility_id', 'occurred_at']);
            });
        }

        // ── 5. Geography: City ────────────────────────────────────────────────
        if (! Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('region_id')->constrained()->cascadeOnDelete();
                $table->uuid('district_id')->nullable(); // FK to country_districts
                $table->string('name');
                $table->string('code')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['region_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('analytics_access_logs');
        Schema::dropIfExists('report_definitions');
        Schema::dropIfExists('dashboard_metrics');
        Schema::dropIfExists('signed_download_tokens');
        Schema::dropIfExists('file_classifications');
        Schema::dropIfExists('virus_scan_results');
        Schema::dropIfExists('go_live_approvals');
        Schema::dropIfExists('go_live_blockers');
        Schema::dropIfExists('go_live_checklist_items');
        Schema::dropIfExists('go_live_checklists');
        Schema::dropIfExists('guardian_relationships');
        Schema::dropIfExists('record_correction_decisions');
        Schema::dropIfExists('patient_rights_requests');
    }
};
