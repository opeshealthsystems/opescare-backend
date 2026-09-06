<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two facts about a laboratory result the table could not record.
 *
 *   entered_by   WHO TYPED IT. `verified_by` was being stamped with the
 *                enterer at entry time, which made "entered" and "verified"
 *                the same person by construction and left no room for a
 *                second-person validation. From now on entry stamps
 *                `entered_by`; release (LabDeskService::release) stamps
 *                `verified_by`. Nullable: rows entered before this column
 *                existed read honestly as "enterer not recorded".
 *
 *   flag_source  WHERE THE FLAG CAME FROM. `lab` = the bench (or the analyser)
 *                recorded it; `range` = derived from the laboratory's own
 *                free-text reference interval; `published` = derived from a
 *                published `reference_ranges` row (the only source that
 *                carries critical bounds). A derived flag is stored so every
 *                reader agrees on it, and its source is stored alongside so no
 *                reader can later present it as the laboratory's own verdict.
 *                Null on a flagged row = recorded by the laboratory (legacy);
 *                null on an unflagged row = no flag, no derivation.
 *                See App\Support\ResultFlagResolver.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_results')) {
            return;
        }

        Schema::table('lab_results', function (Blueprint $table) {
            if (! Schema::hasColumn('lab_results', 'entered_by')) {
                $table->uuid('entered_by')->nullable()->after('verified_by');
            }

            if (! Schema::hasColumn('lab_results', 'flag_source')) {
                $table->string('flag_source', 20)->nullable()->after('flag');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lab_results')) {
            return;
        }

        Schema::table('lab_results', function (Blueprint $table) {
            foreach (['entered_by', 'flag_source'] as $column) {
                if (Schema::hasColumn('lab_results', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
