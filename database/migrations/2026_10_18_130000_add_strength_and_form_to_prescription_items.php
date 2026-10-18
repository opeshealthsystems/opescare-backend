<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Strength and pharmaceutical form as their own fields on a prescription line.
 *
 * WHO good prescribing asks for the medicine (INN), its strength and its form
 * as separate elements; until now a line held only free-text `drug_name` and
 * `dose`. Both columns are nullable: existing lines stay as written (the sheet
 * falls back to the catalogue medicine's strength/form, and prints nothing
 * when neither is known — it never guesses).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            if (! Schema::hasColumn('prescription_items', 'strength')) {
                $table->string('strength', 100)->nullable()->after('drug_code');
            }
            if (! Schema::hasColumn('prescription_items', 'dosage_form')) {
                $table->string('dosage_form', 100)->nullable()->after('strength');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            foreach (['dosage_form', 'strength'] as $column) {
                if (Schema::hasColumn('prescription_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
