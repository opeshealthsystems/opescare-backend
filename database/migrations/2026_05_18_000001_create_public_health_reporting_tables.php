<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pharmacy Inventories (High-fidelity source for stock-out tracking)
        Schema::create('pharmacy_inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->string('medicine_name');
            $table->string('generic_name');
            $table->string('form');
            $table->string('strength');
            $table->string('stock_status')->default('in_stock'); // in_stock, low_stock, out_of_stock
            $table->integer('available_quantity')->default(0);
            $table->boolean('is_expired')->default(false);
            $table->boolean('is_recalled')->default(false);
            $table->boolean('is_quarantined')->default(false);
            $table->timestamp('last_stock_update')->useCurrent();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
        });

        // 2. Blood Inventories (High-fidelity source for blood availability monitoring)
        Schema::create('blood_inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('facility_id')->index();
            $table->string('blood_group'); // O+, O-, A+, etc.
            $table->string('component'); // whole_blood, packed_red_cells, fresh_frozen_plasma, platelets
            $table->integer('available_units')->default(0);
            $table->boolean('is_expired')->default(false);
            $table->boolean('is_quarantined')->default(false);
            $table->boolean('is_unsafe')->default(false);
            $table->timestamp('last_stock_update')->useCurrent();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
        });

        // 3. Report Types Configuration catalog
        Schema::create('public_health_report_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sensitivity_level')->default('aggregate'); // aggregate, de_identified, identifiable
            $table->boolean('default_review_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Reporting Rules configurations
        Schema::create('public_health_reporting_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_type_id')->index();
            $table->string('trigger_source'); // diagnoses, lab_results, pharmacy_stock, blood_stock
            $table->text('trigger_condition')->nullable();
            $table->string('aggregation_level')->default('facility'); // facility, district, region, national
            $table->boolean('requires_review')->default(true);
            $table->boolean('allows_auto_submit')->default(false);
            $table->boolean('requires_patient_identity')->default(false);
            $table->boolean('two_person_approval_required')->default(false);
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamp('effective_to')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();

            $table->foreign('report_type_id')->references('id')->on('public_health_report_types')->onDelete('cascade');
        });

        // 5. Canonical Reports
        Schema::create('public_health_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_type_id')->index();
            $table->uuid('facility_id')->index();
            $table->uuid('district_id')->nullable()->index();
            $table->uuid('region_id')->nullable()->index();
            $table->timestamp('reporting_period_start')->useCurrent();
            $table->timestamp('reporting_period_end')->useCurrent();
            $table->string('status')->default('draft'); // draft, pending_review, requires_correction, approved_for_submission, submitted, accepted, rejected, cancelled, archived
            $table->string('sensitivity_level')->default('aggregate');
            $table->string('data_classification')->default('public'); // public, internal, sensitive
            $table->boolean('generated_by_system')->default(true);
            $table->integer('data_quality_score')->default(100);
            $table->boolean('requires_review')->default(true);
            $table->boolean('requires_correction')->default(false);
            $table->json('payload_json')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('report_type_id')->references('id')->on('public_health_report_types')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // 6. Report items and sub-aggregations
        Schema::create('public_health_report_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('report_id')->index();
            $table->string('indicator_code');
            $table->string('indicator_name');
            $table->integer('value')->default(0);
            $table->string('age_group')->nullable(); // 0-4, 5-14, 15+, etc.
            $table->string('sex')->nullable(); // male, female
            $table->string('disease_code')->nullable(); // ICD-10 e.g. A00
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('public_health_reports')->onDelete('cascade');
        });

        // 7. Data Quality Checks
        Schema::create('public_health_data_quality_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('report_id')->index();
            $table->string('check_code');
            $table->string('check_name');
            $table->string('status')->default('passed'); // passed, warning, failed
            $table->string('severity')->default('low'); // low, medium, critical
            $table->text('message');
            $table->string('field_reference')->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('public_health_reports')->onDelete('cascade');
        });

        // 8. Snapshots (Dashboard caching)
        Schema::create('public_health_dashboard_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('scope_type'); // facility, district, region, national
            $table->uuid('scope_id')->nullable()->index();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->json('metrics_json');
            $table->timestamps();
        });

        // 9. Report Reviews Queue & Decisions
        Schema::create('public_health_report_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id')->index();
            $table->uuid('reviewer_id')->index();
            $table->string('action'); // approve, request_correction, reject, cancel
            $table->text('comment')->nullable();
            $table->timestamp('reviewed_at')->useCurrent();
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('public_health_reports')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 10. Audited Status state changes history
        Schema::create('public_health_report_status_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('report_id')->index();
            $table->string('old_status');
            $table->string('new_status');
            $table->uuid('changed_by')->index();
            $table->text('reason')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->foreign('report_id')->references('id')->on('public_health_reports')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');
        });

        // 11. Report Versions backup for corrections
        Schema::create('public_health_report_versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('report_id')->index();
            $table->integer('version_number');
            $table->json('payload_json');
            $table->text('change_reason')->nullable();
            $table->uuid('created_by')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('report_id')->references('id')->on('public_health_reports')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // 12. Report Review Assignments
        Schema::create('public_health_report_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('report_id')->index();
            $table->uuid('assigned_to')->index();
            $table->uuid('assigned_by')->index();
            $table->string('assignment_status')->default('assigned'); // assigned, completed, reassigned
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            $table->foreign('report_id')->references('id')->on('public_health_reports')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
        });

        // 13. Submission Profiles mappings
        Schema::create('public_health_submission_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('report_type_id')->index();
            $table->string('destination_type'); // dhis2_api, fhir_endpoint, csv_export, email
            $table->string('endpoint_url');
            $table->string('auth_method')->default('none');
            $table->string('payload_format')->default('json'); // fhir_json, dhis2_json, csv
            $table->json('mapping_rules_json')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('report_type_id')->references('id')->on('public_health_report_types')->onDelete('cascade');
        });

        // 14. Report Submission Tracking
        Schema::create('public_health_report_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id')->index();
            $table->uuid('submission_profile_id')->index();
            $table->string('submission_method');
            $table->string('payload_hash');
            $table->string('status')->default('submitted'); // submitted, accepted, rejected
            $table->string('external_reference')->nullable();
            $table->integer('response_code')->nullable();
            $table->text('safe_response_summary')->nullable();
            $table->uuid('submitted_by')->index();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('public_health_reports')->onDelete('cascade');
            $table->foreign('submission_profile_id')->references('id')->on('public_health_submission_profiles')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('cascade');
        });

        // 15. Exported files audits and protection
        Schema::create('public_health_export_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id')->index();
            $table->string('file_type'); // csv, excel, pdf
            $table->string('file_path');
            $table->string('file_hash');
            $table->uuid('generated_by')->index();
            $table->timestamp('generated_at')->useCurrent();
            $table->integer('download_count')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('public_health_reports')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');
        });

        // 16. Outbreak Disease Signals
        Schema::create('public_health_signals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('signal_type'); // disease_cluster, lab_positivity_spike, medicine_stock_out_cluster, blood_shortage_cluster
            $table->string('status')->default('new_signal'); // new_signal, under_review, dismissed, confirmed, escalated, resolved
            $table->string('scope_type'); // facility, district, region
            $table->uuid('scope_id')->nullable()->index();
            $table->uuid('facility_id')->nullable()->index();
            $table->uuid('district_id')->nullable()->index();
            $table->uuid('region_id')->nullable()->index();
            $table->string('condition_code')->nullable();
            $table->string('indicator_code');
            $table->decimal('baseline_value', 12, 2)->default(0.00);
            $table->decimal('current_value', 12, 2)->default(0.00);
            $table->decimal('increase_percentage', 8, 2)->default(0.00);
            $table->string('confidence_level')->default('medium'); // low, medium, high
            $table->string('severity')->default('low'); // low, medium, critical
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('set null');
        });

        // 17. Signal Analyst Reviews
        Schema::create('public_health_signal_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('signal_id')->index();
            $table->uuid('reviewer_id')->index();
            $table->string('action'); // confirm, dismiss, escalate, resolve
            $table->text('comment')->nullable();
            $table->timestamp('reviewed_at')->useCurrent();
            $table->timestamps();

            $table->foreign('signal_id')->references('id')->on('public_health_signals')->onDelete('cascade');
            $table->foreign('reviewer_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 18. Signal Alert Queue
        Schema::create('public_health_signal_alerts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('signal_id')->index();
            $table->string('recipient_type'); // user, facility, regional_officer
            $table->uuid('recipient_id')->index();
            $table->string('channel'); // email, sms, dashboard
            $table->string('status')->default('queued'); // queued, sent, acknowledged
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->foreign('signal_id')->references('id')->on('public_health_signals')->onDelete('cascade');
        });

        // 19. Baselines
        Schema::create('public_health_baselines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('scope_type');
            $table->uuid('scope_id')->nullable()->index();
            $table->string('indicator_code');
            $table->string('period_type')->default('weekly'); // daily, weekly, monthly
            $table->decimal('baseline_value', 12, 2)->default(0.00);
            $table->timestamp('calculated_at')->useCurrent();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_health_baselines');
        Schema::dropIfExists('public_health_signal_alerts');
        Schema::dropIfExists('public_health_signal_reviews');
        Schema::dropIfExists('public_health_signals');
        Schema::dropIfExists('public_health_export_files');
        Schema::dropIfExists('public_health_report_submissions');
        Schema::dropIfExists('public_health_submission_profiles');
        Schema::dropIfExists('public_health_report_assignments');
        Schema::dropIfExists('public_health_report_versions');
        Schema::dropIfExists('public_health_report_status_history');
        Schema::dropIfExists('public_health_report_reviews');
        Schema::dropIfExists('public_health_dashboard_snapshots');
        Schema::dropIfExists('public_health_data_quality_checks');
        Schema::dropIfExists('public_health_report_items');
        Schema::dropIfExists('public_health_reports');
        Schema::dropIfExists('public_health_reporting_rules');
        Schema::dropIfExists('public_health_report_types');
        Schema::dropIfExists('blood_inventories');
        Schema::dropIfExists('pharmacy_inventories');
    }
};
