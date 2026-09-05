<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns `emergency_access_events` from a RECORD of an override into a
 * time-boxed GRANT that can be checked, expired and taken back.
 *
 * The table already stored who broke the glass, for whom, where and why. What
 * it could not answer was the only question that matters at read time — "is
 * this override still in force?" — so an event was, in effect, permanent. A
 * break-glass that never ends is not an exception, it is a relationship the
 * patient never agreed to.
 *
 *   expires_at       absolute wall clock, stamped at invocation. Reads do not
 *                    slide it; when it passes, the door closes on its own.
 *   revoked_at       the patient (or a governance actor) closed it early.
 *   revoked_by_*     who did that — kept as type + id rather than a FK,
 *                    because the revoker can be a `patients` row (the patient
 *                    themselves, from the mobile app) or a `users` row.
 *   notified_at      when the patient was actually told. NULL after a
 *                    successful invocation means the alert failed to send,
 *                    which is a governance defect, not a detail.
 *   ip_address       where the invocation came from, alongside the same value
 *                    in the companion access_logs row.
 *   is_repeat        this clinician had already broken glass on this patient
 *                    inside the repeat window. Cheap to read in an export;
 *                    the pattern is the abuse signal, not any single event.
 *
 * Every column is nullable / defaulted: rows written before this migration
 * keep their meaning, and a NULL `expires_at` is deliberately treated by
 * EmergencyAccessService as "not an active grant" — the safe reading, never
 * "no expiry".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_access_events', function (Blueprint $table) {
            $table->timestampTz('expires_at')->nullable()->index();
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revoked_by_type', 32)->nullable();
            $table->uuid('revoked_by_id')->nullable();
            $table->timestampTz('notified_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->boolean('is_repeat')->default(false);

            // The throttle and the "does this clinician already hold a grant
            // on this patient" check are both keyed on this pair.
            $table->index(['provider_id', 'patient_id'], 'emergency_access_events_provider_patient_idx');
        });
    }

    public function down(): void
    {
        Schema::table('emergency_access_events', function (Blueprint $table) {
            $table->dropIndex('emergency_access_events_provider_patient_idx');
            $table->dropColumn([
                'expires_at',
                'revoked_at',
                'revoked_by_type',
                'revoked_by_id',
                'notified_at',
                'ip_address',
                'is_repeat',
            ]);
        });
    }
};
