<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Patient-side and facility-list hot paths that the national-scale index pass
 * (2026_10_19_090100) did not cover: the mobile app's own-record lists and
 * the partner prescription list, each "WHERE owner = ? ORDER BY time DESC".
 *
 * CONCURRENTLY + IF NOT EXISTS, outside a transaction, so it is safe on a live
 * database and safe to re-run after an interruption (drop any INVALID index
 * left by an interrupted build first). A column that does not exist in this
 * schema is skipped rather than failing the deploy.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    /** name => [table, [columns...]] */
    private const INDEXES = [
        'prescriptions_patient_created_idx'  => ['prescriptions', ['patient_id', 'created_at']],
        'prescriptions_facility_created_idx' => ['prescriptions', ['facility_id', 'created_at']],
        'appointments_patient_scheduled_idx' => ['appointments', ['patient_id', 'scheduled_at']],
        'lab_results_patient_created_idx'    => ['lab_results', ['patient_id', 'created_at']],
        'official_documents_patient_status_idx' => ['official_documents', ['patient_id', 'status']],
        'mtp_user_idx'                       => ['message_thread_participants', ['user_id']],
        'invoices_patient_created_idx'       => ['invoices', ['patient_id', 'created_at']],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::INDEXES as $name => [$table, $columns]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumns($table, $columns)) {
                continue;
            }

            $cols = implode(', ', array_map(fn ($c) => '"' . $c . '"', $columns));
            DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS {$name} ON \"{$table}\" ({$cols})");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys(self::INDEXES) as $name) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS {$name}");
        }
    }
};
