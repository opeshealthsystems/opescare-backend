<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes a prescription prescribable from the portal, dispensable once, and
 * correctable without ever being overwritten.
 *
 * Three gaps this closes:
 *
 *  1. `prescription_items` carried the drug as free text only. The medicine
 *     finder and the pharmacy stock listing both speak `medicines.id`, so a
 *     prescription written as text could never be matched to the catalogue a
 *     pharmacy actually stocks. `medicine_id` gives both ends one identifier.
 *
 *  2. Dispensing recorded *when* but never *who*.
 *
 *  3. Clinical events are immutable here: a prescription is amended, voided or
 *     marked entered-in-error — never rewritten. That needs somewhere to put
 *     the provenance link and the documented reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('prescriptions', 'dispensed_by')) {
                $table->uuid('dispensed_by')->nullable()->after('dispensed_at')
                    ->comment('User who recorded the dispense');
            }
            if (! Schema::hasColumn('prescriptions', 'amends_prescription_id')) {
                $table->uuid('amends_prescription_id')->nullable()
                    ->comment('Provenance: the prescription this one supersedes');
            }
            if (! Schema::hasColumn('prescriptions', 'voided_at')) {
                $table->timestamp('voided_at')->nullable();
            }
            if (! Schema::hasColumn('prescriptions', 'voided_by')) {
                $table->uuid('voided_by')->nullable();
            }
            if (! Schema::hasColumn('prescriptions', 'void_reason')) {
                $table->text('void_reason')->nullable()
                    ->comment('Required documented reason for void / entered-in-error');
            }
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'amends_prescription_id')) {
                $table->foreign('amends_prescription_id')
                    ->references('id')->on('prescriptions')
                    ->nullOnDelete();
                $table->index('amends_prescription_id');
            }
        });

        Schema::table('prescription_items', function (Blueprint $table) {
            if (! Schema::hasColumn('prescription_items', 'medicine_id')) {
                $table->uuid('medicine_id')->nullable()->after('prescription_id')
                    ->comment('Catalogue link — the same id the medicine finder and pharmacy stock use');
            }
        });

        if (Schema::hasTable('medicines')) {
            Schema::table('prescription_items', function (Blueprint $table) {
                $table->foreign('medicine_id')
                    ->references('id')->on('medicines')
                    ->nullOnDelete();
                $table->index('medicine_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            if (Schema::hasColumn('prescription_items', 'medicine_id')) {
                $table->dropForeign(['medicine_id']);
                $table->dropColumn('medicine_id');
            }
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            if (Schema::hasColumn('prescriptions', 'amends_prescription_id')) {
                $table->dropForeign(['amends_prescription_id']);
            }
            $table->dropColumn(array_values(array_filter([
                Schema::hasColumn('prescriptions', 'dispensed_by') ? 'dispensed_by' : null,
                Schema::hasColumn('prescriptions', 'amends_prescription_id') ? 'amends_prescription_id' : null,
                Schema::hasColumn('prescriptions', 'voided_at') ? 'voided_at' : null,
                Schema::hasColumn('prescriptions', 'voided_by') ? 'voided_by' : null,
                Schema::hasColumn('prescriptions', 'void_reason') ? 'void_reason' : null,
            ])));
        });
    }
};
