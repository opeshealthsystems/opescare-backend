<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make the Master Patient Index functional.
 *
 * The MPI shipped as a service layer with nothing that could call it and, on
 * both sides of the fork, a "merge" that moved no data. Three additive changes
 * give it the state it needs:
 *
 *  1. `patients.merged_into_patient_id` / `merged_at` — a retired record points
 *     at its survivor. Nothing is deleted: `patients.id` parents 101 foreign
 *     keys, 88 of them ON DELETE CASCADE, and a merge that deletes is a merge
 *     that destroys the history it was meant to unify.
 *
 *  2. `mpi_candidates` gains the survivor chosen, the reviewer's reason, how
 *     the pair was found, and a UNIQUE on the canonical (source, target) pair —
 *     the service used one query PER PAIR to avoid duplicates, and a unique
 *     index is what that check should always have been.
 *
 *  3. `patient_merges` — the merge record: which rows moved, which were kept
 *     back because the survivor already held a one-per-patient row, which
 *     demographic fields were filled on the survivor. Everything an unmerge
 *     needs to be exact rather than approximate.
 *
 * Every column added is nullable; the new table is empty. Reversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->uuid('merged_into_patient_id')->nullable()->index();
            $table->timestamp('merged_at')->nullable();

            $table->foreign('merged_into_patient_id')
                ->references('id')->on('patients')
                ->nullOnDelete();
        });

        Schema::table('mpi_candidates', function (Blueprint $table) {
            $table->uuid('survivor_patient_id')->nullable()->index();
            $table->text('review_reason')->nullable();
            // 'scan' | 'identifier_collision' | 'manual'
            $table->string('detected_by', 40)->nullable();

            $table->unique(['source_patient_id', 'target_patient_id'], 'mpi_candidates_pair_unique');
        });

        Schema::create('patient_merges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id')->nullable()->index();
            $table->uuid('survivor_patient_id')->index();
            $table->uuid('retired_patient_id')->index();
            $table->uuid('merged_by')->nullable()->index();
            $table->text('reason')->nullable();

            // [{table, column, moved: [ids], kept: [ids]}, …]
            $table->jsonb('manifest');
            // {field: true} — survivor fields that were NULL and took the retired value
            $table->jsonb('survivorship');
            $table->string('retired_previous_identity_status', 40);

            // merged | unmerged
            $table->string('status', 20)->default('merged')->index();
            $table->uuid('unmerged_by')->nullable();
            $table->timestamp('unmerged_at')->nullable();
            $table->text('unmerge_reason')->nullable();

            $table->timestamps();

            $table->foreign('candidate_id')->references('id')->on('mpi_candidates')->nullOnDelete();
            $table->foreign('merged_by')->references('id')->on('users')->nullOnDelete();
            // RESTRICT on both patients: the merge record is the audit trail of
            // an identity decision and must outlive any attempt to remove
            // either party. A patient row is de-identified, never deleted.
            $table->foreign('survivor_patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('retired_patient_id')->references('id')->on('patients')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_merges');

        Schema::table('mpi_candidates', function (Blueprint $table) {
            $table->dropUnique('mpi_candidates_pair_unique');
            $table->dropColumn(['survivor_patient_id', 'review_reason', 'detected_by']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['merged_into_patient_id']);
            $table->dropColumn(['merged_into_patient_id', 'merged_at']);
        });
    }
};
