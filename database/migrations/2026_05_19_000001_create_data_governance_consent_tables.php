<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Access Logs Table
        Schema::create('access_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->nullable()->index();
            $table->uuid('actor_id')->index();
            $table->string('actor_type');
            $table->uuid('organization_id')->nullable()->index();
            $table->uuid('facility_id')->nullable()->index();
            $table->string('purpose');
            $table->string('data_category');
            $table->string('resource_type');
            $table->uuid('resource_id')->nullable()->index();
            $table->string('access_type');
            $table->boolean('emergency_access')->default(false);
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('set null');
        });

        // 2. Correction Requests Table
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->uuid('requested_by_user_id')->index();
            $table->string('resource_type');
            $table->uuid('resource_id')->index();
            $table->text('reason');
            $table->uuid('supporting_document_id')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->uuid('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('requested_by_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });

        // 3. Data Export Requests Table
        Schema::create('data_export_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->nullable()->index();
            $table->uuid('requested_by_user_id')->index();
            $table->string('export_type');
            $table->jsonb('scope_json')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, expired
            $table->uuid('approved_by')->nullable()->index();
            $table->string('file_path')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('set null');
            $table->foreign('requested_by_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        // 4. Security Incidents Table
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('incident_type');
            $table->string('severity');
            $table->string('status')->default('new'); // new, triaging, contained, resolved
            $table->text('summary');
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamp('contained_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();

            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // 5. Country Policies Table
        Schema::create('country_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_code')->index();
            $table->string('name');
            $table->string('version');
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamp('effective_to')->nullable();
            $table->jsonb('settings_json')->nullable();
            $table->string('status')->default('draft'); // draft, published, retired
            $table->timestamps();
        });

        // 6. Research Access Requests Table
        Schema::create('research_access_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('requesting_organization');
            $table->string('principal_investigator');
            $table->text('purpose');
            $table->uuid('ethics_document_id')->nullable();
            $table->jsonb('requested_dataset_scope_json')->nullable();
            $table->string('status')->default('requested'); // requested, approved, rejected, expired
            $table->uuid('reviewed_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_access_requests');
        Schema::dropIfExists('country_policies');
        Schema::dropIfExists('security_incidents');
        Schema::dropIfExists('data_export_requests');
        Schema::dropIfExists('correction_requests');
        Schema::dropIfExists('access_logs');
    }
};
