<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 48 — Data Dictionary & Permission Governance Models
 *
 * Required by OPESCARE_STRATEGIC_MATURITY_STANDARDS_DATA_DICTIONARY_QA_AND_SCALE_MASTER_PACK.md
 * Workstream 03 (Data Dictionary) and Workstream 06 (Role Permission Matrix).
 *
 * Tables created (all guarded with hasTable):
 *   data_dictionary_entries, field_definitions, module_field_maps,
 *   api_payload_field_maps, import_template_field_maps,
 *   role_permission_matrices, high_risk_permissions,
 *   access_review_schedules, permission_audits
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Workstream 03 — Data Dictionary ──────────────────────────────────

        if (! Schema::hasTable('data_dictionary_entries')) {
            Schema::create('data_dictionary_entries', function (Blueprint $table) {
                $table->uuid('id')->primary();
                // canonical field name in snake_case
                $table->string('field_name')->unique();
                $table->string('display_name');
                $table->text('description')->nullable();
                // text|integer|decimal|boolean|date|datetime|uuid|json|enum
                $table->string('data_type', 50);
                $table->string('module')->nullable();       // owning module
                $table->string('table_name')->nullable();   // primary table
                $table->string('column_name')->nullable();  // exact column
                $table->boolean('is_phi')->default(false);  // Protected Health Info flag
                $table->boolean('is_required')->default(false);
                $table->json('allowed_values')->nullable(); // for enum fields
                $table->string('fhir_path')->nullable();    // FHIR R4 mapping
                $table->string('status', 30)->default('active'); // active|deprecated|proposed
                $table->string('proposed_by')->nullable();
                $table->string('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('deprecated_reason')->nullable();
                $table->timestamps();

                $table->index(['module'], 'dde_module_idx');
                $table->index(['status'], 'dde_status_idx');
                $table->index(['is_phi'], 'dde_phi_idx');
            });
        }

        if (! Schema::hasTable('field_definitions')) {
            Schema::create('field_definitions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('data_dictionary_entry_id')
                      ->constrained('data_dictionary_entries')->cascadeOnDelete();
                $table->string('context')->nullable();       // api|import|ui|report
                $table->string('validation_rules')->nullable(); // Laravel rule string
                $table->integer('min_length')->nullable();
                $table->integer('max_length')->nullable();
                $table->string('example_value')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['data_dictionary_entry_id'], 'fd_entry_idx');
            });
        }

        if (! Schema::hasTable('module_field_maps')) {
            Schema::create('module_field_maps', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('data_dictionary_entry_id')
                      ->constrained('data_dictionary_entries')->cascadeOnDelete();
                $table->string('module');
                $table->string('model_class')->nullable();
                $table->string('column_name');
                $table->string('usage_context')->nullable(); // read|write|filter|display
                $table->boolean('is_indexed')->default(false);
                $table->timestamps();

                $table->index(['module', 'model_class'], 'mfm_module_model_idx');
            });
        }

        if (! Schema::hasTable('api_payload_field_maps')) {
            Schema::create('api_payload_field_maps', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('data_dictionary_entry_id')
                      ->constrained('data_dictionary_entries')->cascadeOnDelete();
                $table->string('api_version', 10)->default('v1');
                $table->string('endpoint_pattern')->nullable(); // e.g. /api/v1/patients
                $table->string('json_key');                    // key in request/response
                $table->string('direction', 10);               // request|response|both
                $table->boolean('is_required_in_payload')->default(false);
                $table->boolean('is_redacted_in_logs')->default(false);
                $table->timestamps();

                $table->index(['api_version', 'endpoint_pattern'], 'apfm_api_ep_idx');
            });
        }

        if (! Schema::hasTable('import_template_field_maps')) {
            Schema::create('import_template_field_maps', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('data_dictionary_entry_id')
                      ->constrained('data_dictionary_entries')->cascadeOnDelete();
                $table->string('import_type');     // patients|staff|medicines|etc.
                $table->string('csv_column_header');
                $table->integer('column_position')->nullable();
                $table->boolean('is_required_in_template')->default(false);
                $table->string('default_value')->nullable();
                $table->string('transformation_hint')->nullable(); // e.g. YYYY-MM-DD
                $table->timestamps();

                $table->index(['import_type'], 'itfm_type_idx');
            });
        }

        // ── Workstream 06 — Role Permission Matrix ───────────────────────────

        if (! Schema::hasTable('role_permission_matrices')) {
            Schema::create('role_permission_matrices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('role_name');          // matches roles.name
                $table->string('permission_family');  // patients|billing|labs|etc.
                $table->string('permission_key');     // e.g. patients.view_full
                $table->boolean('is_allowed')->default(false);
                $table->boolean('is_explicitly_blocked')->default(false);
                $table->text('rationale')->nullable();
                $table->string('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['role_name', 'permission_key'], 'rpm_role_perm_uq');
                $table->index(['role_name'], 'rpm_role_idx');
                $table->index(['permission_family'], 'rpm_family_idx');
            });
        }

        if (! Schema::hasTable('high_risk_permissions')) {
            Schema::create('high_risk_permissions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('permission_key')->unique(); // e.g. billing.refund
                $table->text('description');
                $table->boolean('requires_explicit_grant')->default(true);
                $table->boolean('requires_reason')->default(true);
                $table->boolean('requires_approval_workflow')->default(false);
                $table->boolean('requires_periodic_review')->default(true);
                $table->integer('review_interval_days')->default(90);
                $table->boolean('creates_audit_event')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['permission_key'], 'hrp_key_idx');
            });
        }

        if (! Schema::hasTable('access_review_schedules')) {
            Schema::create('access_review_schedules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('role_name')->nullable();       // specific role or null = all
                $table->string('permission_key')->nullable();  // specific perm or null = all high-risk
                $table->string('facility_id')->nullable();
                $table->string('reviewer_user_id')->nullable();
                // monthly|quarterly|biannual|annual
                $table->string('review_frequency', 30)->default('quarterly');
                $table->date('next_review_due');
                $table->date('last_reviewed_at')->nullable();
                $table->string('status', 20)->default('pending'); // pending|in_progress|completed|overdue
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['next_review_due', 'status'], 'ars_due_status_idx');
                $table->index(['role_name'], 'ars_role_idx');
            });
        }

        if (! Schema::hasTable('permission_audits')) {
            Schema::create('permission_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('actor_id');           // who performed the action
                $table->string('target_user_id');     // whose permission changed
                $table->string('action', 50);         // grant|revoke|review|escalate
                $table->string('role_name')->nullable();
                $table->string('permission_key')->nullable();
                $table->string('facility_id')->nullable();
                $table->string('reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at')->useCurrent();
                // append-only — no updated_at
                $table->timestamp('created_at')->useCurrent();

                $table->index(['target_user_id', 'occurred_at'], 'pa_user_time_idx');
                $table->index(['action', 'occurred_at'], 'pa_action_time_idx');
                $table->index(['permission_key'], 'pa_perm_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_audits');
        Schema::dropIfExists('access_review_schedules');
        Schema::dropIfExists('high_risk_permissions');
        Schema::dropIfExists('role_permission_matrices');
        Schema::dropIfExists('import_template_field_maps');
        Schema::dropIfExists('api_payload_field_maps');
        Schema::dropIfExists('module_field_maps');
        Schema::dropIfExists('field_definitions');
        Schema::dropIfExists('data_dictionary_entries');
    }
};
