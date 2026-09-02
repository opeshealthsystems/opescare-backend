<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blood Finder — a patient's request to reserve blood units at a facility.
 *
 * Structural twin of `medicine_reservations` (Medicine Finder): a hold the
 * patient shows at the counter, never a dispense and never a payment. The
 * availability side already exists — `blood_availability`, searched by
 * App\Modules\CareMap\Services\BloodAvailabilitySearchService — so nothing here
 * duplicates stock; this table only records patient intent against it.
 *
 * `care_facility_id` (not `facility_id`) points at `care_facilities`, the
 * public directory that carries latitude/longitude — the same table
 * `blood_availability.facility_id` references. The explicit name keeps it
 * unambiguous against `facilities`, the internal tenant record, and matches
 * medicine_reservations.
 *
 * `blood_availability_id` is a soft link to the availability row that was on
 * screen when the patient asked. It is nullOnDelete: a blood bank rotating its
 * stock rows must never erase a patient's request history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();            // OC-BL-XXXXXXXX, shown at the counter
            $table->uuid('patient_id');
            $table->uuid('care_facility_id');
            $table->uuid('blood_availability_id')->nullable(); // availability row seen at request time
            $table->string('blood_group');                     // App\Enums\BloodGroup value
            $table->string('component_type')->default('whole_blood'); // App\Enums\BloodComponentType
            $table->integer('quantity')->default(1);           // units requested
            $table->string('urgency')->default('routine');     // App\Enums\BloodRequestUrgency
            $table->string('status')->default('pending');      // App\Enums\BloodRequestStatus
            $table->string('contact_phone')->nullable();       // reachable number for the blood bank
            $table->text('patient_note')->nullable();
            $table->text('facility_note')->nullable();
            $table->string('cancelled_reason')->nullable();
            $table->timestamp('needed_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('care_facility_id')->references('id')->on('care_facilities')->cascadeOnDelete();
            $table->foreign('blood_availability_id')->references('id')->on('blood_availability')->nullOnDelete();

            $table->index(['patient_id', 'status']);
            $table->index(['care_facility_id', 'status']);
            $table->index(['blood_group', 'component_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_requests');
    }
};
