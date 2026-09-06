<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a clinician SAID about a laboratory result when they acknowledged it.
 *
 * The acknowledgement itself stays in `notification_events` (the results
 * inbox's ledger — see MobileResultsInboxController); this table is the
 * patient-facing projection of it: the plain-language note the patient may
 * read, the clinician's private comment, and the next step they chose.
 *
 * APPEND-ONLY. A correction is a new row, never an update — the model refuses
 * updates and deletes. `shared_at` is the only thing that decides whether the
 * patient sees `patient_note`; `clinician_comment` is never shown to the
 * patient under any circumstances.
 *
 * `acknowledgement_event_id` is `notification_events.uuid` (the ledger row
 * that this interpretation was written alongside), not its bigint id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_result_interpretations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lab_order_id');
            $table->uuid('patient_id');
            $table->uuid('facility_id')->nullable();
            $table->uuid('author_user_id');
            $table->text('patient_note')->nullable();
            $table->text('clinician_comment')->nullable();
            $table->string('next_action', 24)->nullable(); // none|prescribe|follow_up|message|repeat_test
            $table->uuid('acknowledgement_event_id')->nullable();
            $table->timestampTz('shared_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();

            // Patient erasure is the ONE sanctioned cascade (see LabOrder's
            // docblock); the order cascade follows the same retention path.
            $table->foreign('lab_order_id')->references('id')->on('lab_orders')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
            // A clinician's statement about a patient outlives their account:
            // the author row cannot be removed while a note of theirs stands.
            $table->foreign('author_user_id')->references('id')->on('users')->restrictOnDelete();

            $table->index(['lab_order_id', 'created_at']);
            $table->index(['patient_id', 'shared_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_result_interpretations');
    }
};
