<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The billing / cashier desk: the few columns the 2026-05 billing tables
 * never had, added nullable and additive so nothing existing changes shape.
 *
 * `cashier_sessions` shipped with an opened_at, a closed_at and a running
 * cash total — no float, no count, no variance. A till that cannot record
 * what was counted against what was expected cannot be reconciled, and a
 * variance that is not written down is a variance that gets "fixed". The
 * three amounts are stored, the difference is stored, and nothing in the
 * desk ever adjusts a payment to make them agree.
 *
 * `invoices` had no way to say WHO voided it or WHY; `refunds` had one
 * free-text `processed_by` for both the requester and the approver. The two
 * signatures on a refund are the whole point of a two-step refund, so they
 * get two columns.
 *
 * Whole francs are written into these decimal(12,2) columns (XAF has no
 * minor unit); the desk casts them back to int on read.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->decimal('opening_float_amount', 12, 2)->nullable()->after('cash_total_amount');
            $table->decimal('expected_cash_amount', 12, 2)->nullable()->after('opening_float_amount');
            $table->decimal('counted_cash_amount', 12, 2)->nullable()->after('expected_cash_amount');
            $table->decimal('variance_amount', 12, 2)->nullable()->after('counted_cash_amount');
            $table->text('closing_note')->nullable()->after('variance_amount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('created_by')->nullable()->after('facility_id');
            $table->timestampTz('voided_at')->nullable()->after('paid_at');
            $table->uuid('voided_by')->nullable()->after('voided_at');
            $table->text('void_reason')->nullable()->after('voided_by');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->uuid('requested_by')->nullable()->after('processed_by');
            $table->uuid('approved_by')->nullable()->after('requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn(['requested_by', 'approved_by']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'voided_at', 'voided_by', 'void_reason']);
        });

        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->dropColumn(['opening_float_amount', 'expected_cash_amount', 'counted_cash_amount', 'variance_amount', 'closing_note']);
        });
    }
};
