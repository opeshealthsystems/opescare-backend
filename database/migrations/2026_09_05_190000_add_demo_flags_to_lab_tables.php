<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demo provenance for the laboratory tables.
 *
 * `lab_orders` and `lab_results` predate the demo estate and carry no demo
 * marker at all, so a demo seeder had no honest way to say "this panel is
 * illustrative". Every other seeded table in this codebase (patients,
 * facilities, users, official_documents) carries the same pair, so the demo
 * reset tooling can find and re-seed its own rows without ever touching a real
 * clinical record.
 *
 * DELIBERATELY NOT the IsDemoRecord global scope on App\Models\LabOrder /
 * App\Models\LabResult:
 *
 *   - Those two models are read by the patient portal, the USSD menu, the
 *     governance export, the medical-record PDF and the critical-value
 *     service. Bolting a global scope onto them would silently change what
 *     every one of those returns, in both directions (real rows vanish in
 *     demo mode, demo rows vanish in production).
 *   - The isolation those callers actually need is already there and is
 *     stronger: a lab row is reachable only through its `patient_id`, and
 *     `patients` DOES carry the IsDemoRecord scope. A demo login can only
 *     ever resolve to a demo patient, so it can only ever reach demo labs.
 *
 * So the column records provenance; it is not the access control.
 */
return new class extends Migration
{
    /** The tables that gain the marker, in dependency order. */
    private const TABLES = ['lab_orders', 'lab_results'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'is_demo')) {
                    // Default false: every row that already exists was written
                    // by a real clinical path, and back-filling it to true
                    // would relabel real records as illustrative.
                    $blueprint->boolean('is_demo')->default(false)->index();
                }

                if (! Schema::hasColumn($table, 'demo_seed_key')) {
                    // Stable handle for a seeder, so re-running it updates the
                    // same row instead of stacking duplicate panels.
                    $blueprint->string('demo_seed_key')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                foreach (['is_demo', 'demo_seed_key'] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->dropColumn($column);
                    }
                }
            });
        }
    }
};
