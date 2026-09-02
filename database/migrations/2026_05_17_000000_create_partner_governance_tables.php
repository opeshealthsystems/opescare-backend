<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. partners
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('partner_type');
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('country_code', 2)->default('CM');
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->string('status')->default('draft');
            $table->string('trust_level')->default('level_0_unverified');
            $table->string('risk_level')->default('low');
            $table->decimal('quality_score', 5, 2)->nullable();
            $table->unsignedBigInteger('primary_contact_id')->nullable();
            $table->timestamps();
        });

        // 2. partner_facilities
        Schema::create('partner_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('facility_type');
            $table->string('facility_name');
            $table->string('facility_code')->nullable();
            $table->string('license_number')->nullable();
            $table->string('country_code', 2)->default('CM');
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // 3. partner_professionals
        Schema::create('partner_professionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->onDelete('set null');
            $table->foreignUuid('user_id')->nullable(); // Reference to main users table
            $table->string('professional_type');
            $table->string('full_name');
            $table->string('license_number')->nullable();
            $table->string('licensing_body')->nullable();
            $table->string('specialty')->nullable();
            $table->date('license_expires_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // 4. partner_contacts
        Schema::create('partner_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('preferred_language', 2)->default('en');
            $table->string('contact_type');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // Add FK to partners now that contacts exist
        Schema::table('partners', function (Blueprint $table) {
            $table->foreign('primary_contact_id')->references('id')->on('partner_contacts')->onDelete('set null');
        });

        // 5. partner_documents
        Schema::create('partner_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('document_type');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('status')->default('uploaded');
            $table->date('expiry_date')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });

        // 6. partner_agreements
        Schema::create('partner_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('agreement_type');
            $table->string('version')->default('1.0');
            $table->string('status')->default('draft');
            $table->date('effective_from')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamp('signed_by_partner_at')->nullable();
            $table->timestamp('signed_by_opescare_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        // 7. partner_contribution_permissions
        Schema::create('partner_contribution_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('facility_id')->nullable()->constrained('partner_facilities')->onDelete('cascade');
            $table->string('contribution_type');
            $table->json('allowed_data_categories_json')->nullable();
            $table->boolean('requires_review')->default(false);
            $table->boolean('requires_validation')->default(false);
            $table->date('effective_from');
            $table->date('expires_at')->nullable();
            $table->string('status')->default('active');
            $table->uuid('approved_by')->nullable();
            $table->timestamps();
        });

        // 8. partner_access_permissions
        Schema::create('partner_access_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('facility_id')->nullable()->constrained('partner_facilities')->onDelete('cascade');
            $table->string('access_type');
            $table->json('allowed_data_scopes_json')->nullable();
            $table->json('purpose_allowed_json')->nullable();
            $table->boolean('requires_consent')->default(true);
            $table->date('effective_from');
            $table->date('expires_at')->nullable();
            $table->string('status')->default('active');
            $table->uuid('approved_by')->nullable();
            $table->timestamps();
        });

        // 9. partner_integrations
        Schema::create('partner_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('facility_id')->nullable()->constrained('partner_facilities')->onDelete('cascade');
            $table->string('integration_type');
            $table->string('environment')->default('sandbox');
            $table->string('status')->default('not_started');
            $table->json('allowed_scopes_json')->nullable();
            $table->json('webhook_domains_json')->nullable();
            $table->string('rate_limit_policy')->nullable();
            $table->timestamp('certified_at')->nullable();
            $table->timestamp('production_enabled_at')->nullable();
            $table->timestamps();
        });

        // 10. partner_contributions
        Schema::create('partner_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('facility_id')->nullable()->constrained('partner_facilities')->onDelete('cascade');
            $table->string('contribution_type');
            $table->string('source_system')->nullable();
            $table->string('resource_type');
            $table->string('resource_id');
            $table->string('status')->default('pending');
            $table->string('quality_status')->nullable();
            $table->string('review_status')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });

        // 11. partner_quality_scores
        Schema::create('partner_quality_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('score', 5, 2);
            $table->string('score_level');
            $table->json('metrics_json')->nullable();
            $table->timestamps();
        });

        // 12. partner_risk_scores
        Schema::create('partner_risk_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('risk_level');
            $table->decimal('risk_score', 5, 2);
            $table->json('risk_factors_json')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('calculated_at');
            $table->timestamps();
        });

        // 13. partner_governance_cases
        Schema::create('partner_governance_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->string('case_type');
            $table->string('status')->default('new');
            $table->string('severity')->default('info');
            $table->uuid('assigned_to')->nullable();
            $table->text('description');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropForeign(['primary_contact_id']);
        });

        Schema::dropIfExists('partner_governance_cases');
        Schema::dropIfExists('partner_risk_scores');
        Schema::dropIfExists('partner_quality_scores');
        Schema::dropIfExists('partner_contributions');
        Schema::dropIfExists('partner_integrations');
        Schema::dropIfExists('partner_access_permissions');
        Schema::dropIfExists('partner_contribution_permissions');
        Schema::dropIfExists('partner_agreements');
        Schema::dropIfExists('partner_documents');
        Schema::dropIfExists('partner_contacts');
        Schema::dropIfExists('partner_professionals');
        Schema::dropIfExists('partner_facilities');
        Schema::dropIfExists('partners');
    }
};
