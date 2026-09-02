<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Import Jobs ───────────────────────────────────────────
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('facility_id')->nullable()->index();
            $table->string('import_type');          // patients, staff, appointments, pharmacy_stock, etc.
            $table->string('status')->default('uploaded');
            // uploaded | mapping_required | preview_ready | validated | validation_failed
            // approved_for_import | importing | completed | completed_with_errors | failed | rolled_back | cancelled
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('file_extension', 10);   // csv, xlsx, xls
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->json('detected_headers')->nullable();  // array of column names from file
            $table->json('mapping')->nullable();            // { file_col: system_field, ... }
            $table->json('validation_summary')->nullable(); // { total, valid, invalid, duplicates, to_create }
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('created_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('import_started_at')->nullable();
            $table->timestamp('import_completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // ── Import Row Errors ─────────────────────────────────────
        Schema::create('import_row_errors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('import_job_id')->index();
            $table->unsignedInteger('row_number');
            $table->string('field')->nullable();
            $table->string('error_code');
            $table->text('message');
            $table->json('row_data')->nullable();    // raw row values for display
            $table->timestamps();

            $table->foreign('import_job_id')
                  ->references('id')->on('import_jobs')
                  ->onDelete('cascade');
        });

        // ── Import Mappings (saved for reuse) ─────────────────────
        Schema::create('import_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('facility_id')->nullable()->index();
            $table->string('import_type');
            $table->string('name');                 // user-given label, e.g. "Our patient CSV format"
            $table->json('mapping');                // { file_col: system_field }
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'import_type']);
        });

        // ── Import Audit ──────────────────────────────────────────
        Schema::create('import_audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('import_job_id')->index();
            $table->string('action');               // uploaded, mapping_saved, validated, approved, started, completed, rolled_back, cancelled
            $table->string('actor_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('occurred_at')->useCurrent();

            $table->foreign('import_job_id')
                  ->references('id')->on('import_jobs')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_audit_events');
        Schema::dropIfExists('import_mappings');
        Schema::dropIfExists('import_row_errors');
        Schema::dropIfExists('import_jobs');
    }
};
