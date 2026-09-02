<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integration Certification Tables
 *
 * Tracks whether an external integration (HIS, ERP, LIS, mobile app, etc.)
 * meets OpesCare's published interoperability and security standards.
 *
 * Tables:
 * - integration_certifications:   Master certification record per integration
 * - certification_requirements:   Standard checklist items (platform-wide)
 * - certification_test_runs:      Each time a tester runs the certification suite
 * - certification_badges:         Issued badge when certification is fully passed
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Certification Requirements (catalog) ──────────────────────────────
        Schema::create('certification_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 100)->unique();         // e.g. 'fhir_r4_patient_read'
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 60);               // fhir|security|data_quality|availability|consent
            $table->string('severity', 20)->default('required'); // required|recommended|optional
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        // ── Integration Certifications ─────────────────────────────────────
        Schema::create('integration_certifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('integration_name', 200);       // e.g. "Sagex HIS v3.1"
            $table->string('integration_type', 60);        // his|lis|erp|mobile|sdk|bridge|pharmacy|insurance
            $table->string('vendor_name', 200)->nullable();
            $table->string('vendor_contact', 200)->nullable();
            $table->string('version', 50)->nullable();
            $table->uuid('integration_client_id')->nullable(); // FK to integration_clients if applicable
            $table->uuid('facility_id')->nullable();           // null = platform-level certification
            $table->string('status', 30)->default('in_progress'); // in_progress|passed|failed|expired|revoked
            $table->string('certification_level', 30)->nullable(); // bronze|silver|gold|platinum
            $table->text('scope_description')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('certified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('certified_by', 100)->nullable();
            $table->text('certification_notes')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'integration_type']);
            $table->index('integration_client_id');
        });

        // ── Certification Test Runs ────────────────────────────────────────
        Schema::create('certification_test_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('integration_certification_id');
            $table->string('run_label', 100)->nullable();   // e.g. "Pre-certification run 1"
            $table->string('status', 30)->default('pending'); // pending|running|passed|failed|cancelled
            $table->integer('total_requirements')->default(0);
            $table->integer('passed_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->jsonb('results_json')->nullable();       // per-requirement result details
            $table->text('run_notes')->nullable();
            $table->string('run_by', 100)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('integration_certification_id')
                  ->references('id')
                  ->on('integration_certifications')
                  ->cascadeOnDelete();
            $table->index(['integration_certification_id', 'status']);
        });

        // ── Certification Badges ───────────────────────────────────────────
        Schema::create('certification_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('integration_certification_id');
            $table->string('badge_code', 100)->unique();    // OC-CERT-XXXXXX
            $table->string('certification_level', 30);      // bronze|silver|gold|platinum
            $table->string('integration_name', 200);
            $table->string('integration_type', 60);
            $table->string('issued_by', 100);
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_public')->default(true);    // visible on public verification page
            $table->string('revoke_reason', 300)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('integration_certification_id')
                  ->references('id')
                  ->on('integration_certifications')
                  ->cascadeOnDelete();
            $table->index(['badge_code']);
            $table->index(['is_public', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certification_badges');
        Schema::dropIfExists('certification_test_runs');
        Schema::dropIfExists('integration_certifications');
        Schema::dropIfExists('certification_requirements');
    }
};
