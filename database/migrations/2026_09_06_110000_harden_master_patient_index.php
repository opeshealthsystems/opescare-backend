<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harden the Master Patient Index — one regression, one omission.
 *
 * THE REGRESSION. The previous migration put ON DELETE RESTRICT on both
 * patient foreign keys of `patient_merges`, on the reasoning that a merge
 * record must outlive its parties. But AccountDeletionService's purge step
 * really does `$patient->delete()` once the retention period lapses — that is
 * the whole point of "purged" — and RESTRICT would make that throw for any
 * patient who had ever been part of a merge. A nightly job that fails
 * forever for one person is worse than a merge record with a NULL in it.
 *
 * So: the keys become ON DELETE SET NULL, the columns nullable, and the
 * record keeps BOTH Health IDs as plain text — the audit trail survives a
 * purge without pointing at a row that no longer exists. An unmerge whose
 * party was purged is refused by the service; there is nothing to restore.
 *
 * THE OMISSION. `mpi_candidates` needs a `superseded` status: when a patient
 * is merged away, every other pending pair it appeared in is no longer a
 * question about that patient. The status column is a free string, so no
 * schema change — noted here so the vocabulary is in one place:
 * pending_review | merged | rejected | superseded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_merges', function (Blueprint $table) {
            $table->dropForeign('patient_merges_survivor_patient_id_foreign');
            $table->dropForeign('patient_merges_retired_patient_id_foreign');
        });

        Schema::table('patient_merges', function (Blueprint $table) {
            $table->uuid('survivor_patient_id')->nullable()->change();
            $table->uuid('retired_patient_id')->nullable()->change();

            $table->string('survivor_health_id', 30)->nullable();
            $table->string('retired_health_id', 30)->nullable();

            $table->foreign('survivor_patient_id')->references('id')->on('patients')->nullOnDelete();
            $table->foreign('retired_patient_id')->references('id')->on('patients')->nullOnDelete();
        });

        // Snapshot the Health IDs for any merge already recorded.
        DB::statement("
            UPDATE patient_merges m
               SET survivor_health_id = s.health_id,
                   retired_health_id  = r.health_id
              FROM patients s, patients r
             WHERE s.id = m.survivor_patient_id
               AND r.id = m.retired_patient_id
               AND (m.survivor_health_id IS NULL OR m.retired_health_id IS NULL)");
    }

    public function down(): void
    {
        Schema::table('patient_merges', function (Blueprint $table) {
            $table->dropForeign(['survivor_patient_id']);
            $table->dropForeign(['retired_patient_id']);
            $table->dropColumn(['survivor_health_id', 'retired_health_id']);
        });

        Schema::table('patient_merges', function (Blueprint $table) {
            $table->foreign('survivor_patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('retired_patient_id')->references('id')->on('patients')->restrictOnDelete();
        });
    }
};
