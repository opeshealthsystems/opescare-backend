<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Document Templates Table
        Schema::create('document_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('template_code')->unique();
            $table->string('document_type')->index();
            $table->string('language', 10)->default('en');
            $table->string('version')->default('1.0');
            $table->string('status')->default('draft'); // draft, published, archived
            $table->text('html_template');
            $table->text('css_styles')->nullable();
            $table->text('plain_text_template')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            // Demo columns
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();
        });

        // 2. Official Documents Table
        Schema::create('official_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_type')->index();
            $table->string('document_number')->unique();
            $table->string('verification_code')->unique();
            $table->uuid('patient_id')->nullable()->index();
            $table->string('health_id')->nullable()->index();
            $table->uuid('facility_id')->nullable()->index();
            $table->uuid('organization_id')->nullable()->index();
            $table->uuid('issuer_user_id')->nullable()->index();
            $table->uuid('template_id')->index();
            $table->string('template_version');
            $table->string('status')->default('draft'); // draft, issued, amended, superseded, revoked, entered_in_error
            $table->string('version')->default('1.0');
            $table->string('sensitivity_level')->default('normal');
            $table->string('title');
            $table->json('payload_json');
            $table->json('standard_mapping_json')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('document_hash')->nullable();
            $table->string('payload_hash')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            // Demo columns
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('template_id')->references('id')->on('document_templates')->onDelete('restrict');
        });

        // 3. Document Verification Tokens Table
        Schema::create('document_verification_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('official_document_id')->index();
            $table->string('token_hash')->unique();
            $table->string('status')->default('active'); // active, expired, revoked
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Demo columns
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('official_document_id')->references('id')->on('official_documents')->onDelete('cascade');
        });

        // 4. Document Versions Table
        Schema::create('document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('official_document_id')->index();
            $table->string('version');
            $table->json('payload_json');
            $table->json('standard_mapping_json')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('document_hash')->nullable();
            $table->string('payload_hash')->nullable();
            $table->text('change_reason')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Demo columns
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('official_document_id')->references('id')->on('official_documents')->onDelete('cascade');
        });

        // 5. Document Signatures Table
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('official_document_id')->index();
            $table->uuid('signer_user_id')->nullable();
            $table->string('signer_name');
            $table->string('signer_role');
            $table->string('signer_license_number')->nullable();
            $table->string('signer_license_body')->nullable();
            $table->string('signature_type');
            $table->timestamp('signed_at');
            $table->json('signature_metadata_json')->nullable();
            $table->timestamps();

            // Demo columns
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('official_document_id')->references('id')->on('official_documents')->onDelete('cascade');
        });

        // 6. Document Verification Events Table
        Schema::create('document_verification_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('official_document_id')->nullable()->index();
            $table->string('verification_code')->nullable()->index();
            $table->string('token_hash')->nullable()->index();
            $table->string('result');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->uuid('verified_by_user_id')->nullable();
            $table->boolean('public_verification')->default(true);
            $table->timestamps();

            // Demo columns
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('official_document_id')->references('id')->on('official_documents')->onDelete('set null');
        });

        // 7. Document Access Logs Table
        Schema::create('document_access_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('official_document_id')->index();
            $table->string('actor_id')->index();
            $table->string('actor_type');
            $table->string('action');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Demo columns
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('official_document_id')->references('id')->on('official_documents')->onDelete('cascade');
        });

        // 8. Document Share Links Table
        Schema::create('document_share_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('official_document_id')->index();
            $table->string('share_token_hash')->unique();
            $table->uuid('created_by')->index();
            $table->string('recipient_contact')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->integer('access_count')->default(0);
            $table->integer('max_access_count')->nullable();
            $table->timestamps();

            // Demo columns
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('official_document_id')->references('id')->on('official_documents')->onDelete('cascade');
        });

        // 9. Document Code Mappings Table
        Schema::create('document_code_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('official_document_id')->index();
            $table->string('resource_type')->index();
            $table->string('local_code');
            $table->string('standard_code')->index();
            $table->string('code_system')->index();
            $table->string('mapping_status')->default('unmapped'); // unmapped, mapped, needs_review, rejected, deprecated
            $table->uuid('mapped_by')->nullable();
            $table->timestamp('mapped_at')->nullable();
            $table->timestamps();

            // Demo columns
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('official_document_id')->references('id')->on('official_documents')->onDelete('cascade');
        });

        // 10. Document Specimen Events Table
        Schema::create('document_specimen_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('official_document_id')->index();
            $table->string('sample_id')->index();
            $table->string('event_type'); // collected, received, transferred, processed, stored, rejected, disposed
            $table->uuid('performed_by')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('timestamp');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Demo columns
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();
            $table->string('demo_reset_group')->nullable()->index();

            $table->foreign('official_document_id')->references('id')->on('official_documents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_specimen_events');
        Schema::dropIfExists('document_code_mappings');
        Schema::dropIfExists('document_share_links');
        Schema::dropIfExists('document_access_logs');
        Schema::dropIfExists('document_verification_events');
        Schema::dropIfExists('document_signatures');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('document_verification_tokens');
        Schema::dropIfExists('official_documents');
        Schema::dropIfExists('document_templates');
    }
};
