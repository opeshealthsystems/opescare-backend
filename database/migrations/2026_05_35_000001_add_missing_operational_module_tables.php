<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 35 – Missing Operational Module Tables
 *
 * Adds tables required by Modules 18, 19, 20, and 41 that were
 * specified as BUILD_IF_MISSING but whose tables did not exist:
 *
 *   - appointment_check_ins   (Module 18)
 *   - queue_stations          (Module 19)
 *   - refunds                 (Module 20)
 *   - import_batches          (Module 34)
 *   - import_rollbacks        (Module 34)
 *   - visit_steps             (Module 41)
 *   - visit_timelines         (Module 41)
 *
 * All use UUID primary keys (HasUuids trait on models).
 * All are idempotent via Schema::hasTable() guards.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Module 18: Appointment Check-In ─────────────────────────────────
        if (!Schema::hasTable('appointment_check_ins')) {
            Schema::create('appointment_check_ins', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('appointment_id');
                $table->uuid('patient_id');
                $table->uuid('facility_id')->nullable();
                $table->string('checked_in_by')->nullable(); // staff member email/id
                $table->string('check_in_mode')->default('manual'); // manual|qr|self_service
                $table->string('status')->default('checked_in'); // checked_in|cancelled
                $table->timestamp('checked_in_at');
                $table->timestamp('cancelled_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('appointment_id');
                $table->index('patient_id');
                $table->index('checked_in_at');
            });
        }

        // ── Module 19: Queue Station ─────────────────────────────────────────
        if (!Schema::hasTable('queue_stations')) {
            Schema::create('queue_stations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->uuid('queue_id')->nullable(); // links to facility_queues
                $table->string('name');                // e.g. "Triage Bay 1"
                $table->string('station_type');        // triage|consultation|lab|pharmacy|cashier|radiology
                $table->string('status')->default('active'); // active|inactive|busy
                $table->string('current_operator')->nullable(); // staff user id
                $table->integer('display_order')->default(0);
                $table->boolean('is_priority_station')->default(false);
                $table->timestamps();

                $table->index(['facility_id', 'station_type']);
                $table->index(['facility_id', 'status']);
            });
        }

        // ── Module 20: Refunds ───────────────────────────────────────────────
        if (!Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('payment_id');
                $table->uuid('invoice_id')->nullable();
                $table->uuid('patient_id');
                $table->uuid('facility_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('XOF');
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->string('status')->default('pending'); // pending|approved|processed|rejected
                $table->string('refund_method')->nullable(); // cash|wallet|bank_transfer|mobile_money
                $table->string('processed_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->string('reference_number')->nullable();
                $table->timestamps();

                $table->index('payment_id');
                $table->index('patient_id');
                $table->index('status');
            });
        }

        // ── Module 34: Import Batches ────────────────────────────────────────
        if (!Schema::hasTable('import_batches')) {
            Schema::create('import_batches', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('import_job_id');
                $table->integer('batch_number')->default(1);
                $table->integer('total_rows')->default(0);
                $table->integer('processed_rows')->default(0);
                $table->integer('successful_rows')->default(0);
                $table->integer('failed_rows')->default(0);
                $table->string('status')->default('pending'); // pending|processing|completed|failed
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('error_summary')->nullable();
                $table->timestamps();

                $table->index('import_job_id');
                $table->index('status');
            });
        }

        // ── Module 34: Import Rollbacks ──────────────────────────────────────
        if (!Schema::hasTable('import_rollbacks')) {
            Schema::create('import_rollbacks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('import_job_id');
                $table->string('initiated_by');
                $table->string('reason')->nullable();
                $table->string('status')->default('pending'); // pending|processing|completed|failed
                $table->integer('rows_rolled_back')->default(0);
                $table->text('rollback_log')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index('import_job_id');
            });
        }

        // ── Module 41: Visit Steps ───────────────────────────────────────────
        if (!Schema::hasTable('visit_steps')) {
            Schema::create('visit_steps', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('visit_id');
                $table->string('step_name');             // check_in|triage|consultation|lab|pharmacy|billing|checkout
                $table->string('step_type');             // clinical|administrative|financial
                $table->string('status')->default('pending'); // pending|in_progress|completed|skipped
                $table->integer('display_order')->default(0);
                $table->string('assigned_to')->nullable();  // staff user id/email
                $table->uuid('station_id')->nullable();     // links to queue_stations
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('visit_id');
                $table->index(['visit_id', 'step_name']);
                $table->index('status');
            });
        }

        // ── Module 41: Visit Timeline ────────────────────────────────────────
        if (!Schema::hasTable('visit_timelines')) {
            Schema::create('visit_timelines', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('visit_id');
                $table->uuid('visit_step_id')->nullable();
                $table->string('event_type');           // step_started|step_completed|note_added|status_changed|document_attached
                $table->string('actor_id')->nullable(); // staff/system user
                $table->string('actor_type')->default('user'); // user|system|patient
                $table->json('metadata')->nullable();   // contextual data (old/new status, etc.)
                $table->text('description')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();

                $table->index('visit_id');
                $table->index('visit_step_id');
                $table->index('occurred_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_timelines');
        Schema::dropIfExists('visit_steps');
        Schema::dropIfExists('import_rollbacks');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('queue_stations');
        Schema::dropIfExists('appointment_check_ins');
    }
};
