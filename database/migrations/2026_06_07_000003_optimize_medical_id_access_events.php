<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optimize medical_id_access_events
 *
 * The original schema (2026_05_17) had:
 *   - Only one index: the FK on patient_id
 *   - No index on created_at (renders monthly report queries a full table scan)
 *   - No index on access_type (renders compliance category queries a full table scan)
 *   - No index on facility_id (renders facility-scoped queries a full table scan)
 *   - No notes column (our audit code was writing to it anyway, causing silent failures)
 *
 * This migration adds:
 *
 *   1. notes column (TEXT, nullable) — stores emergency reasons, rejection reasons, etc.
 *   2. Composite index  (patient_id, created_at DESC) — covers the patient portal
 *      access log query: WHERE patient_id = ? ORDER BY created_at DESC LIMIT 25
 *   3. Composite index  (created_at, access_type)     — covers MINSANTE monthly
 *      aggregations: WHERE created_at BETWEEN ? AND ? GROUP BY access_type
 *   4. Index on         facility_id                   — covers facility-scoped
 *      lookups in compliance reports and admin portal
 *   5. Index on         result                        — covers WHERE result = 'denied'
 *      queries in security dashboards
 *
 * Expected improvement on a 500K-row table:
 *   - Patient access log page: full scan → index seek (~2ms vs ~200ms)
 *   - Monthly MINSANTE report query: full scan → range + type scan (~5ms vs ~800ms)
 *   - Facility breach analysis: full scan → index seek (~2ms vs ~200ms)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_id_access_events', function (Blueprint $table) {
            // ── New column ────────────────────────────────────────────────────
            // Add notes after user_agent if it doesn't exist
            if (! Schema::hasColumn('medical_id_access_events', 'notes')) {
                $table->text('notes')->nullable()->after('user_agent')
                    ->comment('Emergency reason, rejection reason, or other freeform audit context.');
            }

            // ── Composite: patient timeline (most frequent portal query) ──────
            // WHERE patient_id = ? ORDER BY created_at DESC
            $table->index(['patient_id', 'created_at'], 'idx_mae_patient_timeline');

            // ── Composite: MINSANTE monthly aggregation ───────────────────────
            // WHERE created_at BETWEEN ? AND ? GROUP BY access_type, result
            $table->index(['created_at', 'access_type'], 'idx_mae_period_type');

            // ── Facility audit scope ──────────────────────────────────────────
            // WHERE facility_id = ? (admin facility audit reports)
            $table->index('facility_id', 'idx_mae_facility');

            // ── Result filter for security dashboards ─────────────────────────
            // WHERE result = 'denied' OR WHERE result = 'success'
            $table->index('result', 'idx_mae_result');

            // ── health_id lookup ──────────────────────────────────────────────
            // Used in QR scan verification: WHERE health_id = ? ORDER BY created_at DESC
            $table->index(['health_id', 'created_at'], 'idx_mae_health_id_timeline');
        });
    }

    public function down(): void
    {
        Schema::table('medical_id_access_events', function (Blueprint $table) {
            $table->dropIndex('idx_mae_patient_timeline');
            $table->dropIndex('idx_mae_period_type');
            $table->dropIndex('idx_mae_facility');
            $table->dropIndex('idx_mae_result');
            $table->dropIndex('idx_mae_health_id_timeline');

            if (Schema::hasColumn('medical_id_access_events', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
