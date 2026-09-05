<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A patient's REQUEST for a laboratory test.
 *
 * This is not a lab order and must never be mistaken for one. A `lab_orders`
 * row is a clinical instruction issued by a clinician; a row here is a patient
 * asking for one. The two are separate tables precisely so that no patient
 * write can ever land in the clinician's order book — the request has to be
 * reviewed and, if approved, turned into a `lab_orders` row by the care team.
 *
 * The row is append-mostly, the same way App\Models\Prescription is: what was
 * asked for, by whom and when is fixed at submission, and a change of mind is
 * a `cancelled` status, never a rewrite and never a delete. See
 * App\Models\LabTestRequest for the enforcement.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lab_test_requests')) {
            return;
        }

        Schema::create('lab_test_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Whose request. Written from the bearer token's patient, never
            // from anything the caller sends.
            $table->uuid('patient_id')->index();

            // Where they are asking. Nullable: a patient may ask their care
            // team generally, without naming a laboratory.
            $table->uuid('facility_id')->nullable()->index();

            // The catalogue entry as it was at the moment of asking. The name
            // is stored alongside the code so the request still reads
            // correctly if the catalogue is later re-published or a facility
            // retires the code.
            $table->string('test_code');
            $table->string('test_name');
            $table->string('loinc_code')->nullable();

            // routine | urgent | stat — how urgently the PATIENT is asking.
            // Advisory: the reviewing clinician sets the order's real urgency.
            $table->string('priority')->default('routine');

            // submitted | approved | declined | cancelled
            $table->string('status')->default('submitted')->index();

            // "Why you want this test", in the patient's own words.
            $table->text('clinical_notes')->nullable();

            $table->timestamp('submitted_at');

            // Filled in by the reviewing clinician, not by the patient.
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_reason')->nullable();

            // If approved, the order that was raised from it. This is the only
            // link between a patient's ask and a clinical order.
            $table->uuid('lab_order_id')->nullable()->index();

            // Demo provenance — see the lab-tables migration for why this is a
            // marker and not a global scope.
            $table->boolean('is_demo')->default(false)->index();
            $table->string('demo_seed_key')->nullable()->index();

            $table->timestamps();

            $table->index(['patient_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_requests');
    }
};
