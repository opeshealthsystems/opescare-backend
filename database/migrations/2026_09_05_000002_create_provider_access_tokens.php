<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile sessions for clinicians.
 *
 * Until now the mobile API was patient-scoped end to end: every route resolved a
 * `patient_id` from a PatientAccessToken, and there was no token a clinician
 * could hold. That is why a doctor could write a prescription on the web desk
 * but not from the app.
 *
 * Deliberately a SEPARATE table rather than a `type` column on
 * patient_access_tokens. The two carry different authority over different
 * subjects — a patient token speaks for one patient's own record, a provider
 * token acts on other people's records within a facility — and one table with a
 * discriminator is exactly how a query that forgets the discriminator turns a
 * patient session into a clinician one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_access_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The clinician's portal user. Their StaffProfile supplies the
            // facility; the token never carries a caller-supplied facility.
            $table->uuid('user_id');

            // Same split as the patient token: an indexed SHA-256 of the bearer
            // for O(1) lookup, plus a bcrypt hash verified in constant time.
            $table->string('token_lookup_hash', 64)->unique();
            $table->string('token_hash');
            $table->string('token_prefix', 16)->nullable();

            // The facility the session is scoped to, captured at login. A
            // clinician who moves facility gets a new session rather than a
            // token that silently follows them.
            $table->uuid('facility_id')->nullable();

            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('device_name', 120)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_access_tokens');
    }
};
