<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 36 – Supplemental Operational Module Tables
 *
 * Adds tables required by the MISSING_OPERATIONAL_MODULES document that
 * had no corresponding DB tables yet:
 *
 * Appointments (Module 5):
 *   - appointment_types
 *   - appointment_reminders
 *   - appointment_cancellations
 *   - appointment_status_histories
 *
 * Queue (Module 6):
 *   - queue_transfers
 *   - queue_priority_rules
 *   - queue_display_settings
 *   - queue_status_histories
 *
 * Billing (Module 7):
 *   - price_list_items
 *   - invoice_adjustments
 *   - payment_methods
 *   - payment_reconciliations
 *   - financial_audits
 *
 * All use UUID primary keys. All are idempotent via hasTable() guards.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ══════════════════════════════════════════════════════════════════
        // APPOINTMENTS MODULE
        // ══════════════════════════════════════════════════════════════════

        // Appointment Types (consultation, follow-up, emergency, telemedicine, etc.)
        if (!Schema::hasTable('appointment_types')) {
            Schema::create('appointment_types', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id')->nullable(); // null = global type
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->string('category')->default('outpatient'); // outpatient|inpatient|emergency|telemedicine
                $table->integer('default_duration_minutes')->default(30);
                $table->boolean('requires_provider')->default(true);
                $table->boolean('requires_referral')->default(false);
                $table->boolean('is_telemedicine')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->index(['facility_id', 'is_active']);
                $table->index('category');
            });
        }

        // Appointment Reminders (schedule + delivery tracking)
        if (!Schema::hasTable('appointment_reminders')) {
            Schema::create('appointment_reminders', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('appointment_id');
                $table->string('channel');         // sms|email|whatsapp|push
                $table->string('status')->default('pending'); // pending|sent|failed|cancelled
                $table->integer('remind_before_hours')->default(24);
                $table->timestamp('scheduled_at');
                $table->timestamp('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('retry_count')->default(0);
                $table->timestamps();

                $table->index('appointment_id');
                $table->index(['status', 'scheduled_at']);
            });
        }

        // Appointment Cancellations (structured cancellation records)
        if (!Schema::hasTable('appointment_cancellations')) {
            Schema::create('appointment_cancellations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('appointment_id');
                $table->string('cancelled_by');         // actor user id/email
                $table->string('cancelled_by_type');    // patient|staff|provider|system
                $table->string('reason_code')->nullable(); // patient_request|no_show|provider_unavailable|facility_closed|other
                $table->text('reason_notes')->nullable();
                $table->boolean('slot_released')->default(false);
                $table->boolean('refund_applicable')->default(false);
                $table->uuid('refund_id')->nullable();
                $table->timestamp('cancelled_at');
                $table->timestamps();

                $table->index('appointment_id');
            });
        }

        // Appointment Status Histories (full status transition log)
        if (!Schema::hasTable('appointment_status_histories')) {
            Schema::create('appointment_status_histories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('appointment_id');
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->string('changed_by')->nullable();
                $table->string('changed_by_type')->default('user');
                $table->string('reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('changed_at');
                $table->timestamps();

                $table->index('appointment_id');
                $table->index('changed_at');
            });
        }

        // ══════════════════════════════════════════════════════════════════
        // QUEUE MODULE
        // ══════════════════════════════════════════════════════════════════

        // Queue Transfers (patient moved from one station to another)
        if (!Schema::hasTable('queue_transfers')) {
            Schema::create('queue_transfers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('from_ticket_id');
                $table->uuid('to_ticket_id')->nullable();
                $table->uuid('from_station_id')->nullable();
                $table->uuid('to_station_id')->nullable();
                $table->uuid('visit_id')->nullable();
                $table->string('transferred_by');
                $table->string('reason')->nullable();
                $table->string('reason_code')->nullable(); // lab_required|billing|pharmacy|consultation|referral|other
                $table->timestamp('transferred_at');
                $table->timestamps();

                $table->index('from_ticket_id');
                $table->index('visit_id');
            });
        }

        // Queue Priority Rules (per-facility priority configuration)
        if (!Schema::hasTable('queue_priority_rules')) {
            Schema::create('queue_priority_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->uuid('queue_id')->nullable();
                $table->string('rule_name');
                $table->string('rule_type');         // age|emergency|disability|pregnancy|vip|appointment_type
                $table->json('conditions');           // {age_above: 65} / {appointment_type: "emergency"}
                $table->integer('priority_boost')->default(0); // added to base priority score
                $table->boolean('requires_confirmation')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->index(['facility_id', 'is_active']);
            });
        }

        // Queue Display Settings (per-facility/station public display configuration)
        if (!Schema::hasTable('queue_display_settings')) {
            Schema::create('queue_display_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id');
                $table->uuid('station_id')->nullable(); // null = facility-wide
                $table->string('display_mode')->default('ticket_number'); // ticket_number|first_name_initial|masked
                $table->boolean('show_waiting_count')->default(true);
                $table->boolean('show_estimated_wait')->default(false);
                $table->boolean('show_called_list')->default(true);
                $table->integer('called_list_count')->default(5);
                $table->boolean('audio_enabled')->default(false);
                $table->string('audio_language')->default('en');
                $table->json('custom_branding')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['facility_id', 'station_id']);
            });
        }

        // Queue Status Histories
        if (!Schema::hasTable('queue_status_histories')) {
            Schema::create('queue_status_histories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('queue_ticket_id');
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->uuid('station_id')->nullable();
                $table->string('changed_by')->nullable();
                $table->string('reason')->nullable();
                $table->timestamp('changed_at');
                $table->timestamps();

                $table->index('queue_ticket_id');
                $table->index('changed_at');
            });
        }

        // ══════════════════════════════════════════════════════════════════
        // BILLING MODULE
        // ══════════════════════════════════════════════════════════════════

        // Price List Items (individual items within a PriceList)
        if (!Schema::hasTable('price_list_items')) {
            Schema::create('price_list_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('price_list_id');
                $table->string('item_code')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category');          // consultation|lab|pharmacy|procedure|radiology|other
                $table->decimal('unit_price', 12, 2);
                $table->string('currency', 3)->default('XOF');
                $table->string('unit')->default('each');
                $table->boolean('is_insurance_billable')->default(true);
                $table->boolean('requires_authorization')->default(false);
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('price_list_id');
                $table->index(['price_list_id', 'category', 'is_active']);
                $table->index('item_code');
            });
        }

        // Invoice Adjustments (discounts, corrections, write-offs)
        if (!Schema::hasTable('invoice_adjustments')) {
            Schema::create('invoice_adjustments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('invoice_id');
                $table->string('adjustment_type');   // discount|surcharge|write_off|correction|insurance_credit
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('XOF');
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->string('adjusted_by');
                $table->string('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->boolean('requires_approval')->default(false);
                $table->timestamps();

                $table->index('invoice_id');
            });
        }

        // Payment Methods (per-facility configured payment channels)
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id')->nullable(); // null = platform-wide
                $table->string('method_type');       // cash|mobile_money|card|bank_transfer|insurance|wallet|voucher
                $table->string('provider_name')->nullable(); // Orange Money, MTN, Moov, Visa, etc.
                $table->string('display_name');
                $table->boolean('is_active')->default(true);
                $table->boolean('requires_reference')->default(false);
                $table->boolean('is_digital')->default(false);
                $table->json('configuration')->nullable(); // provider-specific config
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->index(['facility_id', 'is_active']);
                $table->index('method_type');
            });
        }

        // Payment Reconciliations (cashier session end-of-day reconciliation)
        if (!Schema::hasTable('payment_reconciliations')) {
            Schema::create('payment_reconciliations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('cashier_session_id')->nullable();
                $table->uuid('facility_id');
                $table->date('reconciliation_date');
                $table->string('reconciled_by');
                $table->decimal('expected_cash', 12, 2)->default(0);
                $table->decimal('actual_cash', 12, 2)->default(0);
                $table->decimal('expected_digital', 12, 2)->default(0);
                $table->decimal('actual_digital', 12, 2)->default(0);
                $table->decimal('variance', 12, 2)->default(0);
                $table->string('status')->default('draft'); // draft|submitted|approved|disputed
                $table->text('notes')->nullable();
                $table->string('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['facility_id', 'reconciliation_date']);
                $table->index('status');
            });
        }

        // Financial Audits (structured financial action audit trail)
        if (!Schema::hasTable('financial_audits')) {
            Schema::create('financial_audits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('facility_id')->nullable();
                $table->string('event_type');        // invoice_created|invoice_issued|payment_recorded|refund_processed|adjustment_made|reconciliation_submitted
                $table->string('auditable_type');    // invoice|payment|refund|adjustment
                $table->uuid('auditable_id');
                $table->uuid('patient_id')->nullable();
                $table->string('actor_id');
                $table->string('actor_type')->default('user');
                $table->decimal('amount', 12, 2)->nullable();
                $table->string('currency', 3)->nullable();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();

                $table->index(['auditable_type', 'auditable_id']);
                $table->index(['facility_id', 'event_type']);
                $table->index('occurred_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_audits');
        Schema::dropIfExists('payment_reconciliations');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('invoice_adjustments');
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('queue_status_histories');
        Schema::dropIfExists('queue_display_settings');
        Schema::dropIfExists('queue_priority_rules');
        Schema::dropIfExists('queue_transfers');
        Schema::dropIfExists('appointment_status_histories');
        Schema::dropIfExists('appointment_cancellations');
        Schema::dropIfExists('appointment_reminders');
        Schema::dropIfExists('appointment_types');
    }
};
