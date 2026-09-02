<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legal documents (Terms, Privacy Policy, Consent Policy, DPA, etc.)
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 80)->unique()->index();       // terms-of-use, privacy-policy, etc.
            $table->string('title');
            $table->string('document_type', 60)->index();        // terms|privacy|consent|dpa|facility_agreement|api_terms
            $table->string('language', 10)->default('en')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('requires_acceptance')->default(true);
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // Versioned content for each legal document
        Schema::create('legal_document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('legal_document_id')->index();
            $table->string('version', 20)->index();              // 1.0, 1.1, 2.0
            $table->longText('content_html');                    // rendered HTML
            $table->longText('content_markdown')->nullable();
            $table->string('content_hash', 64)->index();         // SHA-256 of content
            $table->boolean('is_current')->default(false)->index();
            $table->boolean('requires_reacceptance')->default(false);
            $table->string('change_summary')->nullable();
            $table->uuid('published_by')->nullable()->index();
            $table->timestampTz('published_at')->nullable()->index();
            $table->timestampTz('effective_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['legal_document_id', 'version']);
            $table->foreign('legal_document_id')->references('id')->on('legal_documents')->cascadeOnDelete();
            $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
        });

        // User acceptances (patients, staff, facility admins)
        Schema::create('user_legal_acceptances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('legal_document_version_id')->index();
            $table->string('accepted_via', 40)->default('web');  // web|mobile|api|paper
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestampTz('accepted_at')->useCurrent();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'legal_document_version_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('legal_document_version_id')->references('id')->on('legal_document_versions')->cascadeOnDelete();
        });

        // Partner acceptances (facilities, pharmacies, labs, insurers, API clients)
        Schema::create('partner_agreement_acceptances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('legal_document_version_id')->index();
            $table->string('partner_type', 60)->index();         // facility|pharmacy|lab|insurance|api_client
            $table->uuid('partner_id')->index();                 // polymorphic FK to partner tables
            $table->string('accepted_by_name')->nullable();
            $table->string('accepted_by_email')->nullable();
            $table->string('accepted_via', 40)->default('web');
            $table->string('ip_address', 45)->nullable();
            $table->timestampTz('accepted_at')->useCurrent();
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('legal_document_version_id')->references('id')->on('legal_document_versions')->cascadeOnDelete();
        });

        // Account closure requests
        Schema::create('account_closure_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->index();
            $table->string('reason', 300)->nullable();
            $table->string('status')->default('pending')->index(); // pending|approved|rejected|completed
            $table->boolean('data_delete_requested')->default(false);
            $table->boolean('data_export_requested')->default(false);
            $table->uuid('reviewed_by')->nullable()->index();
            $table->text('review_note')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        // Privacy complaints (GDPR/NDPR Article 77 equivalents)
        Schema::create('privacy_complaints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->nullable()->index();
            $table->string('complainant_name')->nullable();
            $table->string('complainant_email')->nullable();
            $table->string('complaint_type', 80)->index();  // unauthorized_access|data_breach|improper_use|denial_of_rights|other
            $table->text('description');
            $table->string('status')->default('open')->index(); // open|under_review|resolved|escalated
            $table->uuid('assigned_to')->nullable()->index();
            $table->text('resolution')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });

        // Minor-to-adult transition reviews
        Schema::create('minor_transition_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id')->unique()->index();
            $table->date('date_of_birth');
            $table->date('turns_18_on');
            $table->string('status')->default('pending')->index(); // pending|notified|consented|transferred|declined
            $table->uuid('guardian_user_id')->nullable()->index();
            $table->uuid('reviewed_by')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestampTz('notified_at')->nullable();
            $table->timestampTz('consented_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minor_transition_reviews');
        Schema::dropIfExists('privacy_complaints');
        Schema::dropIfExists('account_closure_requests');
        Schema::dropIfExists('partner_agreement_acceptances');
        Schema::dropIfExists('user_legal_acceptances');
        Schema::dropIfExists('legal_document_versions');
        Schema::dropIfExists('legal_documents');
    }
};
